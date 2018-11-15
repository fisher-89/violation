<?php 

namespace App;

use Carbon\Carbon;

/**
 * 获取某月开始结束.
 * 
 * @author 28youth
 * @return array
 */
function monthBetween($datetime = ''): array
{
	$time = $datetime ? Carbon::parse($datetime) : Carbon::now();
    $stime = Carbon::create($time->year, $time->month, 01);
	$etime = Carbon::create($time->year, $time->month, $time->daysInMonth);

    return [$stime, $etime];
}

/**
 * 获取阶段时间(默认半年内).
 * 
 * @author 28youth
 * @param  string $start
 * @param  string $end
 * @return array
 */
function stageBetween($stime = '', $etime = ''): array
{
	$toStime = Carbon::parse($stime)->subMonth()->endOfMonth();
	$toEtime = Carbon::parse($etime)->endOfMonth();

	if (!$stime && !$etime) {
		$toStime = Carbon::now()->subMonth(6)->startOfMonth();
		$toEtime = Carbon::now()->endOfMonth();
	}

	return [$toStime, $toEtime];
}