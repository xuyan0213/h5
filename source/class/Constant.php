<?php

namespace Gac163202505;

class Constant
{
    const ALLOWED_DOMAINS = [
        'ghac.weiyihui.cn'
    ];
    /**
     * H5奖品
     */
    const LOTTERY_LIST = [
        1  => '广汽本田1：18车模（凌派）',             //15
        2  => '广汽本田1：18车模（ZR-V致在）',         //2
        3  => '广汽本田1：18车模（飞度Sport）',        //2
        4  => '广汽本田1：18车模（极湃2）',            //2
        5  => '广汽本田1：18车模（极湃1）',            //2
        6  => '广汽本田1：43车模（随机）',             //12
        7  => '小度蓝牙音箱',                       //1
        8  => '临时停车号码牌',                     //3
        9  => '飞度定制不锈钢杯',                   //3
        10 => '飞度定制毛巾',                       //3
        11 => '新能源盲盒',                         //3
        12 => '小度熊花生马克杯',                   //3
        13 => '百度联名运动飞盘',                   //3
    ];

    const LOTTERY_RANK_LIST = [
        14 => '广汽本田1：18车模（凌派）',    //5
        15 => '小度蓝牙音箱',              //1
        16 => '广汽本田1：43车模（随机）',    //1
        17 => '小度熊花生马克杯',          //2
        18 => '百度联名运动飞盘',          //2
        19 => '广汽本田杜邦袋'             //1
    ];

    const LOTTERY_RANK = 19;

    /**
     * 奖品数量
     */
    const LOTTERY_NUM      = 54;
    const LOTTERY_RANK_NUM = 12;

    const CLEAR_MYSQL_LIST = [
        'gx_api_log',
        'gx_prize',
        'gx_prize_record',
        'gx_score_record',
        'gx_setlist',
        'gx_user',
    ];

    const LOTTERY_STATUS_LABEL = [
        200 => '操作成功',
        201 => '已中奖',
        202 => '今日奖池已空',
        203 => '未中奖',
        204 => 'IP黑名单',
        205 => '奖品已空',
        206 => '实物奖品已空',
        207 => '操作数据库失败',
    ];

    const LOTTERY_STATUS = [
        'SUCCESS'      => 200,
        'WIN'          => 201,
        'EMPTY'        => 202,
        'LOSE'         => 203,
        'IP_BLACKLIST' => 204,
        'NO_PRIZE'     => 205,
        'NO_PHYSICAL'  => 206,
        'DB_FAIL'      => 207,
    ];


    /**
     * REDIS前缀
     * @type string
     */
    const REDIS_PREFIX = 'gac_bd_202411_prod:';

    /**
     * REDIS KEY
     */

    /**
     * 是否游戏
     */
    const KEY_GAME = self::REDIS_PREFIX . 'game';
    /**
     * 积分榜单
     */
    const KEY_SCORE_RANK = self::REDIS_PREFIX . 'score_rank';
    /**
     * 黑名单
     */
    const KEY_IP_BLACKLIST = self::REDIS_PREFIX . 'ip_blacklist';
    /**
     * 中奖用户
     */
    const KEY_LUCKIER = self::REDIS_PREFIX . 'luckier';
    /**
     * 排行榜中奖用户
     */
    const KEY_LUCKIER_RANK = self::REDIS_PREFIX . 'luckier_rank';
    /**
     * 抽奖
     */
    const KEY_LOTTERY = self::REDIS_PREFIX . 'lottery';
    /**
     * 排行榜抽奖
     */
    const KEY_LOTTERY_RANK = self::REDIS_PREFIX . 'lottery_rank';
    /**
     * 每日抽奖
     */
    const KEY_LOTTERY_DAY = self::REDIS_PREFIX . 'lottery_day:';
    /**
     * 抽奖锁
     */
    const KEY_LOTTERY_LOCK = self::REDIS_PREFIX . 'lottery_lock';

    /**
     * 实物奖品列表
     */
    const KEY_LOTTERY_PHYSICAL_LIST = self::REDIS_PREFIX . 'lottery_physical_list:';

    /**
     * 排行榜奖品列表
     */
    const KEY_LOTTERY_RANK_LIST = self::REDIS_PREFIX . 'lottery_rank_list';

    /**
     * 抽奖记录
     */
    const KEY_LOTTERY_RECORD = self::REDIS_PREFIX . 'lottery_record';

    /**
     * 排行榜抽奖记录
     */
    const KEY_LOTTERY_RANK_RECORD = self::REDIS_PREFIX . 'lottery_rank_record';
    /**
     * 抽奖概率
     */
    const KEY_LOTTERY_RATE = [self::REDIS_PREFIX . 'lottery_physical_rate', self::REDIS_PREFIX . 'lottery_lose_rate'];

    /**
     * 排行榜抽奖概率
     */
    const KEY_LOTTERY_RANK_RATE = [self::REDIS_PREFIX . 'lottery_rank_rate', self::REDIS_PREFIX . 'lottery_rank_lose_rate'];
    /**
     * 预约
     */
    const KEY_APPOINTMENT = self::REDIS_PREFIX . 'appointment';

    /**
     * 最大用户ID
     */
    const KEY_MAX_USER_ID = self::REDIS_PREFIX . 'max_user_id';
}