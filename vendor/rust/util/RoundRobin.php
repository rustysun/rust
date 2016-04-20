<?php

/**
 * 轮询
 */
class RoundRobin {

    /**
     * @param array $servers
     * @return mixed|null
     */
    public static function getResult(array $servers) {
        static $server_list, $server_num, $last_index;
        if (!$server_list) {
            $server_list = $servers;
            $server_num = count($servers);
            $last_index = -1;
        }
        $last_index = ($last_index + 1) % $server_num;
        $result = $server_list[$last_index];
        return $result;
    }

    /**
     * 获取轮询结果(加权轮询算法)
     * @param array $servers 服务器列表
     * @param int $min 最小权值
     * @param int $max 最大权值
     * @return mixed|null
     */
    public static function getResultByWeight(array $servers = [], $min_weight, $max_weight) {
        static $server_list, $server_num, $last_index;
        static $max, $current_weight, $max_gcd;
        if (!$server_list) {
            $server_list = $servers;
            $server_num = count($servers);
            $max_gcd = self::getGcdFromAllWeights($servers, $min_weight, $server_num);
            $max = $max_weight;
            $last_index = -1;
        }
        $result = NULL;
        while (TRUE) {
            $last_index = ($last_index + 1) % $server_num;
            if ($last_index == 0) {
                $current_weight -= $max_gcd;
                if ($current_weight <= 0) {
                    $current_weight = $max;
                    if ($current_weight == 0) {
                        break;
                    }
                }
            }
            if (($server_list[$last_index]['weight']) >= $current_weight) {
                $result = $server_list[$last_index];
                break;
            }
        }
        return $result;
    }

    /**
     * 获取全部服务器权重中的最大公约数权值
     * @param array $weights 权值列表
     * @param int $min_weight 最小权值
     * @param int $server_num 服务器数量
     * @return int
     */
    protected static function getGcdFromAllWeights(array $servers, $min_weight, $server_num) {
        $weight = (int) $min_weight;
        $is_gcd = FALSE;
        while ($weight >= 1) {
            for ($i = 0; $i < $server_num; $i++) {
                if (0 === $servers[$i]['weight'] % $weight) {
                    $is_gcd = TRUE;
                    break;
                }
            }
            if ($is_gcd) {
                break;
            }
            $weight--;
        }
        return $weight;
    }
}