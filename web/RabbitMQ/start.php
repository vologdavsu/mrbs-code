<?php

namespace MRBS;

require_once __DIR__ . '/vendor/autoload.php';
require_once "rabbitmq_querier.inc";

function log_in_file($msg): void {
    $file = __DIR__ . 'logfile1.txt';

    if(file_exists($file)) {
        $current = file_get_contents($file);
    } else {
        $current = '';
    }

    $current .= $msg;
    file_put_contents($file, $current);
}

function check_messages(): void
{
//    $rmq = new RMQBroker();
//
//    $data = emulate_create_message_from_tt();
//    $rmq->send_message($data);
//    $data = emulate_update_message_from_tt();
//    $rmq->send_message($data);
//    $data = emulate_move_message_from_tt();
//    $rmq->send_message($data);
//    $data = emulate_skip_message_from_tt();
//    $rmq->send_message($data);
//    $data = emulate_un_skip_message_from_tt();
//    $rmq->send_message($data);
//    $data = emulate_un_move_message_from_tt();
//    $rmq->send_message($data);
//    $data = emulate_delete_message_from_tt();
//    $rmq->send_message($data);
//
//    unset($rmq);
    $rmq = new RMQBroker();

    $rmq->receive_message();
}

function emulate_delete_message_from_tt(): string
{
    return create_sending_data("MRBS", ":delete_booking", array(
        "id" => 1
    ));
}

function emulate_create_message_from_tt(): string
{
    return create_sending_data("MRBS", ":create_booking", array(
        "id" => 1,
        "group" => "4Б09 РПС-31",
        "subgroup" => "Все из группы",
        "time" => "11:40 - 13:10",
        "date_start" => "03 Октябрь 2023 г.",
        "date_end" => "12 Декабрь 2023 г.",
        "discipline" => "Разработка пользовательского интерфейса",
        "teacher" => "пр. Дегтярёв М.Е.",
        "location" => "к. 2, ауд. 227/1",
        "type" => "Лекция",
        "is_online" => "Нет",
        "is_odd_week" => "Чётные",
        "week_day" => "Вторник"
    ));
}

function emulate_update_message_from_tt(): string
{
    return create_sending_data("MRBS", ":update_booking", array(
        "id" => 1,
        "group" => "4Б09 РПС-31",
        "subgroup" => "Все из группы",
        "time" => "11:40 - 13:10",
        "date_start" => "03 Октябрь 2023 г.",
        "date_end" => "12 Декабрь 2023 г.",
        "discipline" => "Основы теории управления",
        "teacher" => "доц. Сергушичева А.П.",
        "location" => "к. 2, ауд. 227/1",
        "type" => "Лекция",
        "is_online" => "Нет",
        "is_odd_week" => "Чётные",
        "week_day" => "Вторник"
    ));
}

function emulate_move_message_from_tt(): string
{
    return create_sending_data("MRBS", ":move_booking", array(
        "id" => 1,
        "group" => "4Б09 РПС-31",
        "subgroup" => "Все из группы",
        "time" => "9:30 - 11:10",
        "date_start" => "29 Ноябрь 2023 г.",
        "date_end" => "29 Ноябрь 2023 г.",
        "discipline" => "Основы теории управления",
        "teacher" => "доц. Сергушичева А.П.",
        "location" => "к. 2, ауд. 227/1",
        "type" => "Лекция",
        "is_online" => "Нет",
        "is_odd_week" => "Чётные",
        "week_day" => "Вторник",
        "date" => "28 Ноябрь 2023 г.",
        "time_skip" => "11:40 - 13:10"
    ));
}

function emulate_skip_message_from_tt(): string
{
    return create_sending_data("MRBS", ":skip_booking", array(
        "date" => "14 Ноябрь 2023 г.",
        "time_skip" => "11:40 - 13:10"
    ));
}

function emulate_un_move_message_from_tt(): string
{
    return create_sending_data("MRBS", ":un_move_booking", array(
        "id" => 1,
        "group" => "4Б09 РПС-31",
        "subgroup" => "Все из группы",
        "time" => "11:40 - 13:10",
        "date_start" => "28 Ноябрь 2023 г.",
        "date_end" => "28 Ноябрь 2023 г.",
        "discipline" => "Основы теории управления",
        "teacher" => "доц. Сергушичева А.П.",
        "location" => "к. 2, ауд. 227/1",
        "type" => "Лекция",
        "is_online" => "Нет",
        "is_odd_week" => "Чётные",
        "week_day" => "Вторник",
        "date" => "29 Ноябрь 2023 г.",
        "time_skip" => "9:30 - 11:10"
    ));
}

function emulate_un_skip_message_from_tt(): string
{
    return create_sending_data("MRBS", ":un_skip_booking", array(
        "id" => 1,
        "group" => "4Б09 РПС-31",
        "subgroup" => "Все из группы",
        "time" => "11:40 - 13:10",
        "date_start" => "14 Ноябрь 2023 г.",
        "date_end" => "14 Ноябрь 2023 г.",
        "discipline" => "Основы теории управления",
        "teacher" => "доц. Сергушичева А.П.",
        "location" => "к. 2, ауд. 227/1",
        "type" => "Лекция",
        "is_online" => "Нет",
        "is_odd_week" => "Чётные",
        "week_day" => "Вторник"
    ));
}

check_messages();
