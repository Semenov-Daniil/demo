<?php

namespace common\components;

use Exception;
use yii\base\Component;

class CommandComponent extends Component
{
    /**
     * Calls a Bash script with the specified path and arguments.
     *
     * @param string $scriptPath The path to the Bash script
     * @param array $args Array of arguments for the script
     * @param string $sudoUser The user on whose behalf the command is executed (root by default)
     * @return array Execution result: return code, stdout and stderr
     * @throws Exception If the script does not exist or is not executable
     */
    function executeBashScript(string $scriptPath, array $args = [], string $sudoUser = 'root'): array
    {
        if (!file_exists($scriptPath)) {
            throw new Exception("Script does not exist: $scriptPath");
        }
        if (!is_executable($scriptPath)) {
            throw new Exception("Script is not executable: $scriptPath");
        }

        $escapedScriptPath = escapeshellarg($scriptPath);
        $escapedArgs = array_map('escapeshellarg', $args);
        $escapedSudoUser = escapeshellarg($sudoUser);

        $command = "sudo -u $escapedSudoUser $escapedScriptPath " . implode(' ', $escapedArgs);

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new Exception("Failed to start process for script: $scriptPath");
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        return [
            'returnCode' => $returnCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}