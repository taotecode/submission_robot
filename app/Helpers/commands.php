<?php


function get_command($botInfo,$key)
{
    $commands = $botInfo->bot_command;
    foreach ($commands as $command){
        if ($command->command===$key){
            return $command;
        }
    }
    return false;
}
