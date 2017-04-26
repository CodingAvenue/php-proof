<?php

namespace CodingAvenue\Proof;

/**
 * Class that analyzes a php file
 * Currently has two ways to analyze the php file, PSR2 coding standard and Mess Detection
 */
class Analyzer {
    /** array of Mess Detection rules. This are the default rules of Mess Detection. */
    CONST MD_RULES = array('cleancode', 'codesize', 'controversial', 'design', 'naming', 'unusedcode');

    /** @var file string The path to the php file to be analyze. */
    private $file;

    /**
     * Constructor
     * 
     * @param string $file The php file to be analyze
     */
    public function __construct(string $file)
    {
        if (!file_exists($file)) {
            throw new \Exception("file $file not found.");
        }

        $this->file = $file;
    }

    /**
     * Analyze the coding standard of the php file against PSR2 standards
     *
     * @param array $options Currently only support one option which is skipEndTagMessage which will ignore the closing tag message
     * @return array $violations an array of violation. Each violation is an array with the following elements
     *              - message string The violation message
     *              - line int|string the line number on the php file where the violation occur
     *              - column int|string the column number on the php file where the violation occur.
     *              
     */
    public function codingStandard(array $options = array()): array
    {
        $phpcs = VendorBin::getCS();
        $command = sprintf("%s -q --runtime-set ignore_errors_on_exit 1 --runtime-set ignore_warnings_on_exit 1 --report=json --standard=PSR2 %s 2>&1", $phpcs, $this->file);

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 ) {
            throw new \Exception($output[0]);
        }

        $snifferOutput = json_decode($output[0], true);

        $violations = array();

        foreach ($snifferOutput['files'] as $file) {
            foreach ($file['messages'] as $message) {
                if (array_key_exists('skipEndTagMessage', $options)
                    && $options['skipEndTagMessage'] && $message['message'] === 'A closing tag is not permitted at the end of a PHP file') {
                        continue;
                }

                $violations[] = array(
                    'message'   => $message['message'],
                    'line'      => $message['line'],
                    'column'    => $message['column']
                );
            }
        }

        return $violations;   
    }

    /**
     * Analyze the php file for Mess Detection
     *
     * @param array $rules The rules to be used for the mess detection test.
     * @return array $violations an array of violations. Each element of the array is also an array with the following elements
     *          - message string The violation message
     *          - beginLine int|string the line number on the php file where the violation occur
     *          - endLine int|string the column number on the php file where the violation occur.
     */
    public function messDetection(array $rules = array()): array
    {
        foreach ($rules as $rule) {
            if(!in_array($rule, self::MD_RULES)) {
                throw new \Exception("Unknown rule $rule, Available rules are [" . implode(", ", self::MD_RULES) . "]");
            }
        }

        if (empty($rules)) {
            $rules = self::MD_RULES;
        }

        $phpmd = VendorBin::getMD();
        $command = sprintf("%s %s xml %s --ignore-violations-on-exit --suffixes '' 2>&1", $phpmd, $this->file, implode(",", $rules));

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \Exception($output[0]);
        }

        $xml = simplexml_load_string(implode("", $output));

        $violations = array();

        if ($xml->file) {
            foreach($xml->file->children() as $violation) {
                $violations[] = array(
                    'message'   => trim($violation->__toString()),
                    'beginLine' => $violation['beginline']->__toString(),
                    'endLine'   => $violation['endline']->__toString()
                );
            }
        }

        return $violations;
    }
}
