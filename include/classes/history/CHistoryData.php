<?php

    /*
    ** Zabbix
    ** Copyright (C) 2001-2015 Zabbix SIA
    **
    ** This program is free software; you can redistribute it and/or modify
    ** it under the terms of the GNU General Public License as published by
    ** the Free Software Foundation; either version 2 of the License, or
    ** (at your option) any later version.
    **
    ** This program is distributed in the hope that it will be useful,
    ** but WITHOUT ANY WARRANTY; without even the implied warranty of
    ** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    ** GNU General Public License for more details.
    **
    ** You should have received a copy of the GNU General Public License
    ** along with this program; if not, write to the Free Software
    ** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
    **/


    class CHistoryData {

        public function __construct(array $option = array()) {
            $this->stime = isset($option['stime']) ? $option['stime'] : null;
            $this->itemid = $option['itemid']; // 23944 23945
            $this->sizeX = isset($option['size']) ? $option['size'] : 1200;
            $this->period = isset($option['period']) && !empty($option['period']) && $option['period'] >= SEC_PER_HOUR ? $option['period'] : SEC_PER_HOUR;
            $this->data = array();
            $this->itemsHost = null;
            $this->getItemById();
        }

        private function getItemById() {
            $this->items = get_item_by_itemid($this->itemid);
        }

        public function get() {
            $this->selectData();
            if($this->data[$this->itemid]['avg']){
                $result = array_values($this->data[$this->itemid]['avg']);
            }else{
                $result = array();
            }
            return $result;
        }

        protected function selectData() {
            $this->data = array();
            $now = time();
            if (!isset($this->stime)) {
                $this->stime = $now - $this->period;
            }

            $this->diffTZ = (date('Z', $this->stime) - date('Z', $this->stime + $this->period));
            $this->from_time = $this->stime; // + timeZone offset
            $this->to_time = $this->stime + $this->period; // + timeZone offset

            $p = $this->to_time - $this->from_time; // graph size in time
            $z = $p - $this->from_time % $p; // graphsize - mod(from_time,p) for Oracle...
            $x = $this->sizeX; // graph size in px

            $this->itemsHost = null;

            $config = select_config();

            $item = $this->items;

            $from_time = $this->from_time;
            $to_time = $this->to_time;
            $calc_field = 'round(' . $x . '*' . zbx_sql_mod(zbx_dbcast_2bigint('clock') . '+' . $z, $p) . '/(' . $p . '),0)'; // required for 'group by' support of Oracle

            $sql_arr = array();

            // override item history setting with housekeeping settings
            if ($config['hk_history_global']) {
                $item['history'] = $config['hk_history'];
            }

            $trendsEnabled = $config['hk_trends_global'] ? ($config['hk_trends'] > 0) : ($item['trends'] > 0);

            if (!$trendsEnabled || (($item['history'] * SEC_PER_DAY) > (time() - ($this->from_time + $this->period / 2)) && ($this->period / $this->sizeX) <= (ZBX_MAX_TREND_DIFF / ZBX_GRAPH_MAX_SKIP_CELL))) {
                $this->dataFrom = 'history';

                array_push($sql_arr, 'SELECT itemid,' . $calc_field . ' AS i,' . 'COUNT(*) AS count,AVG(value) AS avg,MIN(value) as min,' . 'MAX(value) AS max,MAX(clock) AS clock' . ' FROM history ' . ' WHERE itemid=' . zbx_dbstr($this->items['itemid']) . ' AND clock>=' . zbx_dbstr($from_time) . ' AND clock<=' . zbx_dbstr($to_time) . ' GROUP BY itemid,' . $calc_field, 'SELECT itemid,' . $calc_field . ' AS i,' . 'COUNT(*) AS count,AVG(value) AS avg,MIN(value) AS min,' . 'MAX(value) AS max,MAX(clock) AS clock' . ' FROM history_uint ' . ' WHERE itemid=' . zbx_dbstr($this->items['itemid']) . ' AND clock>=' . zbx_dbstr($from_time) . ' AND clock<=' . zbx_dbstr($to_time) . ' GROUP BY itemid,' . $calc_field);
            } else {
                $this->dataFrom = 'trends';

                array_push($sql_arr, 'SELECT itemid,' . $calc_field . ' AS i,' . 'SUM(num) AS count,AVG(value_avg) AS avg,MIN(value_min) AS min,' . 'MAX(value_max) AS max,MAX(clock) AS clock' . ' FROM trends' . ' WHERE itemid=' . zbx_dbstr($this->items['itemid']) . ' AND clock>=' . zbx_dbstr($from_time) . ' AND clock<=' . zbx_dbstr($to_time) . ' GROUP BY itemid,' . $calc_field, 'SELECT itemid,' . $calc_field . ' AS i,' . 'SUM(num) AS count,AVG(value_avg) AS avg,MIN(value_min) AS min,' . 'MAX(value_max) AS max,MAX(clock) AS clock' . ' FROM trends_uint ' . ' WHERE itemid=' . zbx_dbstr($this->items['itemid']) . ' AND clock>=' . zbx_dbstr($from_time) . ' AND clock<=' . zbx_dbstr($to_time) . ' GROUP BY itemid,' . $calc_field);

                $this->items['delay'] = max($this->items['delay'], SEC_PER_HOUR);
            }
            $curr_data = &$this->data[$this->itemid];

            $curr_data['count'] = null;
            $curr_data['min'] = null;
            $curr_data['max'] = null;
            $curr_data['avg'] = null;
            $curr_data['clock'] = null;

            foreach($sql_arr as $sql) {
                $result = DBselect($sql);
                while ($row = DBfetch($result)) {
                    $idx = $row['i'] - 1;
                    if ($idx < 0) {
                        continue;
                    }

                    /* --------------------------------------------------
                        We are taking graph on 1px more than we need,
                        and here we are skiping first px, because of MOD (in SELECT),
                        it combines prelast point (it would be last point if not that 1px in begining)
                        and first point, but we still losing prelast point :(
                        but now we've got the first point.
                    --------------------------------------------------*/
                    $curr_data['count'][$idx] = $row['count'];
                    $curr_data['min'][$idx] = $row['min'];
                    $curr_data['max'][$idx] = $row['max'];
                    $curr_data['avg'][$idx] = $row['avg'];
                    $curr_data['clock'][$idx] = $row['clock'];
                    $curr_data['shift_min'][$idx] = 0;
                    $curr_data['shift_max'][$idx] = 0;
                    $curr_data['shift_avg'][$idx] = 0;
                }
            }
            //$curr_data['avg_orig'] = is_array($curr_data['avg']) ? zbx_avg($curr_data['avg']) : null;
            // calculate missed points
            $first_idx = 0;

            /*
                first_idx - last existing point
                ci - current index
                cj - count of missed in one go
                dx - offset to first value (count to last existing point)
            */
            for($ci = 0, $cj = 0; $ci < $this->sizeX; $ci++) {

                if (!isset($curr_data['count'][$ci]) || ($curr_data['count'][$ci] == 0)) {
                    $curr_data['count'][$ci] = 0;
                    $curr_data['shift_min'][$ci] = 0;
                    $curr_data['shift_max'][$ci] = 0;
                    $curr_data['shift_avg'][$ci] = 0;
                    $cj++;
                    continue;
                }

                if ($cj == 0) {
                    continue;
                }

                $dx = $cj + 1;
                $first_idx = $ci - $dx;

                if ($first_idx < 0) {
                    $first_idx = $ci; // if no data from start of graph get current data as first data
                }

                for(; $cj > 0; $cj--) {
                    /*if ($dx < ($this->sizeX / 20) && $this->type == GRAPH_TYPE_STACKED) {
                        $curr_data['count'][$ci - ($dx - $cj)] = 1;
                    }*/

                    foreach(array('clock', 'min', 'max', 'avg') as $var_name) {
                        $var = &$curr_data[$var_name];

                        if ($first_idx == $ci && $var_name == 'clock') {
                            $var[$ci - ($dx - $cj)] = $var[$first_idx] - (($p / $this->sizeX) * ($dx - $cj));
                            continue;
                        }

                        $dy = $var[$ci] - $var[$first_idx];
                        $var[$ci - ($dx - $cj)] = bcadd($var[$first_idx], bcdiv(($cj * $dy), $dx));
                    }
                }
            }

            if ($cj > 0 && $ci > $cj) {
                $dx = $cj + 1;
                $first_idx = $ci - $dx;

                for(; $cj > 0; $cj--) {
                    foreach(array('clock', 'min', 'max', 'avg') as $var_name) {
                        $var = &$curr_data[$var_name];

                        if ($var_name == 'clock') {
                            $var[$first_idx + ($dx - $cj)] = $var[$first_idx] + (($p / $this->sizeX) * ($dx - $cj));
                            continue;
                        }
                        $var[$first_idx + ($dx - $cj)] = $var[$first_idx];
                    }
                }
            }
        }
    }
