<?php

namespace Witify\Devops\Checks;

use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\Health\Checks\Result;

class CpuCheck extends CpuLoadCheck
{
    public function run(): Result
    {
        $cpuLoad = $this->measureCpuLoad();
        $numberOfCores = $this->getNumberOfCores();

        $result = Result::make()
            ->ok()
            ->shortSummary(
                "{$cpuLoad->lastMinute} {$cpuLoad->last5Minutes} {$cpuLoad->last15Minutes}"
            )
            ->meta([
                'last_minute' => $cpuLoad->lastMinute,
                'last_5_minutes' => $cpuLoad->last5Minutes,
                'last_15_minutes' => $cpuLoad->last15Minutes,
            ]);

        if ($cpuLoad->last15Minutes > $numberOfCores) {
            return $result->failed("The CPU load of the last 15 minutes is {$cpuLoad->last15Minutes} which is higher than the number of cores: {$numberOfCores}");
        }

        return $result;
    }

    protected function getNumberOfCores(): int
    {
        $cores = 1;

        if (file_exists('/proc/cpuinfo')) {
            $cpuinfo = (string) file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $cores = count($matches[0]);
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $process = @popen('wmic cpu get NumberOfCores', 'rb');
            if ($process !== false) {
                fgets($process);
                $cores = (int) fgets($process);
                pclose($process);
            }
        } else {
            $cores = (int) shell_exec('sysctl -n hw.ncpu');
        }

        return $cores;
    }
}
