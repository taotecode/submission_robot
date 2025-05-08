<?php


function get_command($botInfo,$key)
{
    $commands = $botInfo->bot_command;
    foreach ($commands as $command){
        if ($command->command===$key){
            $command->data = json_decode($command->data, true);
            return $command;
        }
    }
    return false;
}
