<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender\Connector\Filter;

use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\Filter\AdditionalDateType;

/**
 * Class DateField
 * @package Bitrix\Sender\Connector\Filter
 */
class DateField extends AbstractField
{
	/**
	 * Fetch field value.
	 *
	 * @return array
	 */
	public function fetchFieldValue($filterFields)
	{
		$result = FilterOptions::fetchDateFieldValue(
			$this->getId() . '_datesel',
			$filterFields
		);

		$allowYearName = $this->getAllowYearName();
		if (!isset($result[$allowYearName]) && isset($filterFields[$allowYearName]))
		{
			$result[$allowYearName] = $filterFields[$allowYearName];
		}

		if (!empty($result))
		{
			return $result;
		}

		if (!$this->isCustomDate())
		{
			return [];
		}

		$result = [];
		foreach(['days', 'month', 'year'] as $key)
		{
			$fieldKey = $this->getId() . '_' . $key;
			if (empty($filterFields[$fieldKey]))
			{
				continue;
			}

			$result[$fieldKey] = $filterFields[$fieldKey];
		}

		return $result;
	}

	/**
	 * Apply filter.
	 *
	 * @param array $filter Filter.
	 * @return void
	 */
	public function applyFilter(array &$filter = array())
	{
		$filterKey = $this->getFilterKey();
		$from = $this->getFrom();
		$to = $this->getTo();

		if ($from)
		{
			if ($this->isAllowYears())
			{
				$filter[">=$filterKey"] = $from;
			}
			else
			{
				$filter[] = $this->getFilterYearLess('FROM', $from, ">=");
			}
		}
		if ($to)
		{
			if ($this->isAllowYears())
			{
				$filter["<=$filterKey"] = $to;
			}
			else
			{
				$filter[] = $this->getFilterYearLess('TO', $to, "<=", $from);
			}
		}
		if ($this->getDays())
		{
			$fieldId = $this->getId();
			$filterKey = $this->getId() . '_EXPR_DAYS';
			$filter[] = (new RuntimeFilter())
				->setFilter(
					'=' . $filterKey,
					$this->getDays()
				)
				->addRuntime([
					'name' => $filterKey,
					'expression' => "DAY(%s)",
					'buildFrom' => [$fieldId],
					'parameters' => []
				]);
		}
		if ($this->getMonths())
		{
			$fieldId = $this->getId();
			$filterKey = $this->getId() . '_EXPR_MONTHS';
			$filter[] = (new RuntimeFilter())
				->setFilter(
					'=' . $filterKey,
					$this->getMonths()
				)
				->addRuntime([
					'name' => $filterKey,
					'expression' => "MONTH(%s)",
					'buildFrom' => [$fieldId],
					'parameters' => []
				]);
		}
		if ($this->getYears())
		{
			$fieldId = $this->getId();
			$filterKey = $this->getId() . '_EXPR_YEARS';
			$filter[] = (new RuntimeFilter())
				->setFilter(
					'=' . $filterKey,
					$this->getYears()
				)
				->addRuntime([
					'name' => $filterKey,
					'expression' => "YEAR(%s)",
					'buildFrom' => [$fieldId],
					'parameters' => []
				]);
		}
	}

	/**
	 * Get date from.
	 *
	 * @param string|null $defaultValue Default value.
	 * @return null|string
	 */
	public function getFrom($defaultValue = null)
	{
		return $this->getDate($defaultValue, true);
	}

	/**
	 * Get date to.
	 *
	 * @param string|null $defaultValue Default value.
	 * @return null|string
	 */
	public function getTo($defaultValue = null)
	{
		return $this->getDate($defaultValue, false);
	}

	/**
	 * Get days.
	 *
	 * @return int[]
	 */
	public function getDays()
	{
		return $this->getCustomDateData('days');
	}

	/**
	 * Get months.
	 *
	 * @return int[]
	 */
	public function getMonths()
	{
		return $this->getCustomDateData('month');
	}

	/**
	 * Get years.
	 *
	 * @return int[]
	 */
	public function getYears()
	{
		return $this->getCustomDateData('year');
	}

	private function getCustomDateData($key)
	{
		$key = $this->getId() . '_' . $key;
		$value = $this->getValue();
		if (!is_array($value) || count($value) === 0)
		{
			return [];
		}

		if (empty($value[$key]))
		{
			return [];
		}

		if (!is_array($value[$key]))
		{
			return [];
		}

		return array_map(
			function ($item)
			{
				return (int) $item;
			},
			$value[$key]
		);
	}

	private function getDate($defaultValue = null, $isFrom = true)
	{
		$name = $this->getId();
		$value = $this->getValue();
		if (!is_array($value) || count($value) === 0)
		{
			return $defaultValue;
		}

		$calcData = array();
		FilterOptions::calcDates($name, $value, $calcData);

		if ($isFrom)
		{
			return isset($calcData[$name . '_from']) ? $calcData[$name . '_from'] : $defaultValue;
		}
		else
		{
			return isset($calcData[$name . '_to']) ? $calcData[$name . '_to'] : $defaultValue;
		}
	}

	private function isCustomDate()
	{
		return isset($this->data['include']) && in_array(AdditionalDateType::CUSTOM_DATE, $this->data['include']);
	}

	private function isAllowYears()
	{
		if (!isset($this->data['allow_years_switcher']) || !$this->data['allow_years_switcher'])
		{
			return true;
		}

		return !empty($this->data['value'][$this->getAllowYearName()]);
	}

	private function getAllowYearName()
	{
		return $this->getId() . '_allow_year';
	}

	private function getFilterYearLess($tag, $value, $operation =  "=", $fromValue = null)
	{
		$addOneYear = '';
		$date = new Date($value);
		if ($fromValue)
		{
			$dateFrom = new Date($fromValue);
			if ($dateFrom->getTimestamp() > $date->getTimestamp())
			{
				$addOneYear = '+ 1';
			}
		}

		$fieldId = $this->getId();
		$filterKey = $fieldId . "_YEAR_LESS_" . $tag;
		return (new RuntimeFilter())
			->setFilter(
				"=$filterKey",
				1
			)
			->addRuntime([
				'name' => $filterKey,
				'expression' => "
					case when %s $operation concat(YEAR(%s) $addOneYear, '-{$date->format('m')}-{$date->format('d')}')
					then 1 else 0 end
				",
				'buildFrom' => [
					$this->getFilterKey(),
					$this->getFilterKey()
				],
				'parameters' => []
			]);
	}
}