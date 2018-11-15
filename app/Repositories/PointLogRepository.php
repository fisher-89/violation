<?php 

namespace App\Repositories;

use App\Models\PointLog;

class PointLogRepository
{
	/**
	 * PointLog model
	 */
	protected $pointlog;

	/**
     * PointLogRepository constructor.
     * @param PointLog $pointlog
     */
	public function __construct(PointLog $pointlog)
	{
		$this->pointlog = $pointlog;
	}

	/**
	 * 添加积分变更日志.
	 * 
	 * @author 28youth
	 * @param  array  $data
	 * @param  array  $maps
	 * @return mixed
	 */
	public function save(array $data, array $maps = [])
	{

		foreach ($data as $key => $field) {
			$this->pointlog->{$key} = $field;
		}

		if (! empty($maps)) {
			foreach ($maps as $key => $field) {
				$this->pointlog->where($key, $field);
			}
		}

		return $this->pointlog->save();
	}
	
}