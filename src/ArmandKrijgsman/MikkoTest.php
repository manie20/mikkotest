<?php

namespace ArmandKrijgsman;

use \cli\Arguments;

/**
 * MikoTest is used to handle functionality for salary CSV generation from the CLI.
 */
class MikkoTest
{
    /**
     * @var \cli\Arguments
     */
    private $arguments;

    /**
     * @var resource
     */
    private $writeHandle;

    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (php_sapi_name() != 'cli') {
            throw new \Exception('Application should be run through CLI.');
        }

        $this->arguments = $this->setCliOptions();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (null !== $this->writeHandle) {
            fclose($this->writeHandle);
        }
    }

    /**
     * Set the CLI options and return the Arguments for further usage.
     *
     * @return \cli\Arguments
     */
    private function setCliOptions()
    {
        $arguments = new Arguments(
            array('strict' => true)
        );

        $arguments
            ->addFlag(
                array(
                    'help',
                    'h'
                ),
                'Show this help screen'
            )
            ->addOption(
                array('name', 'n'),
                array(
                    'default' => 'payment-dates.csv',
                    'description' => 'Input the filename of the CSV you wish to generate.a'
                )
            )
            ->parse();

        if (true === $arguments['help']) {
            echo $arguments->getHelpScreen();
            echo "\n\n";
            exit;
        }

        return $arguments;
    }

    /**
     * Generate CSV file.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function generate()
    {
        if (null === $fileName = $this->arguments['name']) {
            $fileName  = array_shift(
                $this->arguments->getOption('name')
            );
        }

        $writeToFile = $_SERVER['PWD'] . '/' . $fileName;
        if (true == file_exists($writeToFile)) {
            throw new \Exception(
                sprintf(
                    'File already exists, please remove \'%s\' and try again!',
                    $writeToFile
                )
            );
        }

        $this->writeCsvLine($writeToFile, '"Month name","salary payment date","bonus payment date"');
        foreach ($this->getMonths() as $month) {
            $baseSalaryTimeStamp = $this->getBaseSalaryTimeStamp($month);
            $bonusSalaryTimeStamp = $this->getBonusSalaryTimeStamp($month);
            $this->writeCsvLine(
                $writeToFile,
                sprintf(
                    '"%s","%s","%s"',
                    date('F', $baseSalaryTimeStamp),
                    date('d-m-Y', $baseSalaryTimeStamp),
                    date('d-m-Y', $bonusSalaryTimeStamp)
                )
            );
        }

        return $fileName;
    }

    /**
     * Open writeFile and write line to this file.
     *
     * @param string $writeFile
     * @param string $line
     *
     * @throws \Exception
     */
    private function writeCsvLine($writeFile, $line)
    {
        if (null === $this->writeHandle) {
            $this->writeHandle = fopen($writeFile, 'w');
        }

        if (false === fwrite($this->writeHandle, $line . "\n")) {
            throw new \Exception(
                sprintf(
                    'Unable to write to file: %s',
                    $writeFile
                )
            );
        }
    }

    /**
     * Get months for the rest of current year.
     *
     * @param null $fromTimestamp
     *
     * @return \Generator
     */
    private function getMonths($fromTimestamp = null)
    {
        if (null === $fromTimestamp) {
            $fromTimestamp = time();
        }

        for ($month = date('n', $fromTimestamp); $month<=12; $month++) {
            yield $month;
        }
    }

    /**
     * Get timestamp of the base salary date.
     *
     * @param int $month
     *
     * @return int
     */
    private function getBaseSalaryTimeStamp($month)
    {
        for ($day = 0; $day < 3; $day++) {
            $lastDay = mktime(1, 1, 1, $month+1, 0-$day);

            if (false === $this->isWeekend($lastDay)) {
                break;
            }
        }

        return $lastDay;
    }

    /**
     * Get timestamp of the bonus salary date.
     *
     * @param int   $month
     * @param int   $day
     *
     * @return int
     */
    private function getBonusSalaryTimeStamp($month, $day = 15)
    {
        $lastDay = mktime(1, 1, 1, $month, $day);
        if (false === $this->isWeekend($lastDay)) {
            return $lastDay;
        }

        return strtotime('next wednesday', $lastDay);
    }

    /**
     * Is given day a week or weekend day.
     *
     * @param $timestamp
     *
     * @return boolean
     */
    private function isWeekend($timestamp)
    {
        $day = date('N', $timestamp);
        if ($day > 5) {
            return true;
        }

        return false;
    }
}
