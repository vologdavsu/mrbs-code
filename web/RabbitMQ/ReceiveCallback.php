<?php

namespace MRBS;

global $override_locale, $cli_language, $default_language_tokens, $disable_automatic_language_changing;

use DateTimeZone;
use IntlDateFormatter;
use MRBS\Locale;

require_once(substr(__DIR__, 0, -8) . "mrbs_sql.inc");
require_once(substr(__DIR__, 0, -8) . "dbsys.inc");
require_once(substr(__DIR__, 0, -8) . "defaultincludes.inc");
require_once(substr(__DIR__, 0, -8) . "lib/MRBS/Locale.php");
require_once(substr(__DIR__, 0, -8) . "lib/MRBS/System.php");

class ReceiveCallback
{
    function __construct()
    {
    }

    public function callback($msg): void
    {
        $message = json_decode($msg->getBody(), true);

        if ($this->is_not_execute($message)) {
            return;
        }

        echo "\n[Получено сообщение] ";
        log_in_file("\n[Получено сообщение] ");

        if (explode(":", $message["action"])[1] == "create_booking") {
            $this->create_booking($message['message']);
            return;
        }

        if (explode(":", $message["action"])[1] == "update_booking") {
            $this->update_booking($message['message']);
            return;
        }

        if (explode(":", $message["action"])[1] == "delete_booking") {
            $this->delete_booking($message['message']);
            return;
        }

        if (explode(":", $message["action"])[1] == "move_booking") {
            $this->move_booking($message['message']);
            return;
        }

        if (explode(":", $message["action"])[1] == "un_move_booking") {
            $this->un_move_booking($message['message']);
            return;
        }

        if (explode(":", $message["action"])[1] == "skip_booking") {
            $this->skip_booking($message['message']);
            return;
        }

        if (explode(":", $message["action"])[1] == "un_skip_booking") {
            $this->un_skip_booking($message['message']);
            return;
        }

        echo "Неизвестное имя действия: " . $message["action"];
        log_in_file("Неизвестное имя действия: " . $message["action"]);
    }

    private function create_booking($message, $is_move=false): void
    {
        echo "create_booking => ";
        log_in_file("create_booking => ");
        $valid_msgs = $this->prepare_message($message);
        $res = $this->check_cabinet_existing($valid_msgs[0]["building"], $valid_msgs[0]["audience"]);
        if ($res == null) {
            echo "[WARNING] Такого кабинета не существует. " . 'УК' . $valid_msgs[0]["building"] . ', аудитория ' . $valid_msgs[0]["audience"] . ".";
            log_in_file("[WARNING] Такого кабинета не существует. " . 'УК' . $valid_msgs[0]["building"] . ', аудитория ' . $valid_msgs[0]["audience"] . ".");
            return;
        }
        $room_id = $res;
        $count_errors_collision = 0;
        $count_errors_creating = 0;
        foreach ($valid_msgs as $valid_msg) {
            $booking = array('room_id' => $room_id, 'end_time' => $valid_msg["end_time"], 'start_time' => $valid_msg["start_time"]);
            $res = mrbsCheckFree($booking);
            if (count($res) > 0) {
//                echo implode(', ', $res);
                $message_text = implode(', ', $res);
                $message_title = "MRBS => Обнаружена коллизия";
                mail(\Config::email_for_reporting, $message_title, $message_text);
                $count_errors_collision++;
                continue;
            }
            $valid_msg['room_id'] = $room_id;
            $valid_msg['is_move'] = $is_move;
            try {
                $res = mrbsCreateSingleEntry($valid_msg);
            } catch (\Throwable $th) {
                echo $th;
                $count_errors_creating++;
            }
        }
        $error_msg = ($count_errors_collision > 0) ? ". Обнаружена коллизия (Сообщение отправлено на почту elgaevav+booking@vogu35.ru) " : " ";
        echo count($valid_msgs) - $count_errors_collision - $count_errors_creating . " из " . count($valid_msgs) . " Записей бронирования создано" . $error_msg;
        log_in_file(count($valid_msgs) - $count_errors_collision - $count_errors_creating . " из " . count($valid_msgs) . " Записей бронирования создано" . $error_msg);
    }
    private function check_cabinet_existing($building, $audience): ?int
    {
        $sql = "select r.id from mrbs_room r
                join mrbs_area a on r.area_id=a.id
                where lower(a.area_name) like lower(?) and lower(r.room_name)=lower(?)";
        $res = db()->query($sql, array('УК' . $building . '%', $audience))->next_row_keyed();
        if ($res == null) {
            return null;
        }
        return $res["id"];
    }

    private function prepare_message($message): array
    {
        $day_skip = ($message["is_odd_week"] == "Все") ? 7 : 14;
        $en_start_date = $this->convert_str_to_date($message["date_start"]);
        $en_end_date = $this->convert_str_to_date($message["date_end"]);

        $amount_lessons = floor($en_end_date->diff($en_start_date)->days / $day_skip) + 1;

        $start_time = $this->convert_str_to_time($message["time"], 0);
        $end_time = $this->convert_str_to_time($message["time"], 1);

        $name = $message["type"] . " \"" . $message["discipline"] . "\" у группы " . $message["group"];
        $description = "Преподаватель - " . $message["teacher"];

        $building = explode("к. ", explode(", ", $message["location"])[0])[1];
        $audience = explode("ауд. ", explode(", ", $message["location"])[1])[1];

        $time_interval = array("start" => 0, "end" => 0);
        if (array_key_exists("date", $message)) {
            $time_interval = $this->prepare_date($message["date"], $message["time_skip"]);
        }

        $valid_messages = array();
        for ($i = 0; $i < $amount_lessons; $i++) {
            $valid_msg = array(
                "id_tt" => (int)$message["id"],
                "start_time" => $en_start_date->getTimestamp() + $i * $day_skip * 24 * 60 * 60 + $start_time->getTimestamp() - 60 * 60 * 3,
                "end_time" => $en_start_date->getTimestamp() + $i * $day_skip * 24 * 60 * 60 + $end_time->getTimestamp() - 60 * 60 * 3,
                "name" => $name,
                "type" => "I",
                "description" => $description,
                "building" => $building,
                "audience" => $audience,
                "need_support" => false,
                "need_vks" => false,
                "vks_url" => "",
                "need_translation" => false,
                "translation_url" => "",
                "entry_type" => 1,
                "date_skip_start" => $time_interval["start"],
                "date_skip_end" => $time_interval["end"]
            );
            $valid_messages[] = $valid_msg;
        }
        return $valid_messages;
    }

    private function prepare_date($date, $time): array
    {
        $date_skip_start = $this->convert_str_to_date($date);
        $date_skip_end = $date_skip_start;

        $time_skip_start = $this->convert_str_to_time($time, 0);
        $time_skip_end = $this->convert_str_to_time($time, 1);

        $date_skip_start = $date_skip_start->getTimestamp() + $time_skip_start->getTimestamp() - 60 * 60 * 3;
        $date_skip_end = $date_skip_end->getTimestamp() + $time_skip_end->getTimestamp() - 60 * 60 * 3;
        return array("start" => $date_skip_start, "end" => $date_skip_end);
    }

    private function convert_str_to_date($date): \DateTime|bool
    {
        $ru_months = array('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
        $en_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        $new_date = str_replace($ru_months, $en_months, substr($date, 0, -3));
        return date_create_from_format("j F Y H:i:s", $new_date . "00:00:00", new DateTimeZone('UTC'));
    }

    private function convert_str_to_time($time, $i): \DateTime|bool
    {
        return date_create_from_format("Y-m-d H:i:s", "1970-01-01 " . explode(" - ", $time)[$i] . ":00", new DateTimeZone('UTC'));
    }

    private function update_booking($message): void
    {
        echo "update_booking: ";
        log_in_file("update_booking: ");

//        $valid_msgs = $this->prepare_message($message);
//        $res = $this->check_cabinet_existing($valid_msgs[0]["building"], $valid_msgs[0]["audience"]);
//        if ($res == null) {
//            echo "[WARNING] Такого кабинета не существует. " . 'УК' . $valid_msgs[0]["building"] . ', аудитория ' . $valid_msgs[0]["audience"] . ".";
//            return;
//        }
//
//        $update_count = 0;
//        foreach ($valid_msgs as $valid_msg) {
//            $valid_msg["room_id"] = $res;
//            unset($valid_msg["building"]);
//            unset($valid_msg["audience"]);
//            unset($valid_msg["date_skip_start"]);
//            unset($valid_msg["date_skip_end"]);
//            $id_tt = $valid_msg["id_tt"];
//            unset($valid_msg["id_tt"]);
//
//            $query = "update mrbs_entry set ";
//            foreach ($valid_msg as $key => $val) {
//                if (gettype($val) == "string") {
//                    $query .= $key . "='" . $val . "', ";
//                } else if (gettype($val) == "boolean") {
//                    $query .= $key . "=" . (($val) ? 1 : 0) . ", ";
//                } else if (gettype($val) == "integer") {
//                    $query .= $key . "=" . $val . ", ";
//                }
//            }
//            $query = substr($query, 0, -2);
//            $query .= " where is_move<>1 and id_tt=" . $id_tt;
//
//            try {
//                db()->query($query);
//            } catch (\Throwable $th) {
//                $update_count--;
//            }
//            $update_count++;
//        }
//
//        echo $update_count . " Записей бронирования обновлено";
        $this->delete_booking($message);
        echo "+ ";
        log_in_file("+ ");
        $this->create_booking($message);
    }

    private function delete_booking($message): void
    {
        echo "delete_booking => ";
        log_in_file("delete_booking => ");
        $sql = "select count(id) from mrbs_entry
                where id_tt=?";
        $amount = db()->query($sql, array($message["id"]))->next_row_keyed();

        if ($amount["count(id)"] == 0) {
            echo "Возникли ошибки при удалении записи бронирования ";
            log_in_file("Возникли ошибки при удалении записи бронирования ");
            return;
        }

        $sql = "delete from mrbs_entry
            where id_tt=?";
        db()->query($sql, array($message["id"]))->next_row_keyed();
        echo "Записи бронирования удалены ";
        log_in_file("Записи бронирования удалены ");
    }

    private function move_booking($message): void
    {
        echo "move_booking: ";
        log_in_file("move_booking: ");
        $valid_msgs = $this->prepare_date($message["date"], $message["time_skip"]);
        $this->delete_one_booking_by_date($valid_msgs);
        echo "+ ";
        log_in_file("+ ");
        $this->create_booking($message, true);
    }

    private function un_move_booking($message): void
    {
        echo "un_move_booking: ";
        log_in_file("un_move_booking: ");
        $valid_msgs = $this->prepare_date($message["date"], $message["time_skip"]);
        $this->delete_one_booking_by_date($valid_msgs);
        echo "+ ";
        log_in_file("+ ");
        $this->create_booking($message);
    }

    private function skip_booking($message): void
    {
        echo "skip_booking => ";
        log_in_file("skip_booking => ");
        $valid_msgs = $this->prepare_date($message["date"], $message["time_skip"]);
        $this->delete_one_booking_by_date($valid_msgs);
    }

    private function delete_one_booking_by_date($message): void
    {
        echo "delete_one_booking_by_date => ";
        log_in_file("delete_one_booking_by_date => ");
        $sql = "select count(id) from mrbs_entry
                where start_time=? and end_time=?";
        $amount = db()->query($sql, array($message["start"], $message["end"]))->next_row_keyed();

        if ($amount["count(id)"] == 0) {
            echo "Возникли ошибки при удалении записи бронирования ";
            log_in_file("Возникли ошибки при удалении записи бронирования ");
            return;
        }

        $sql = "delete from mrbs_entry
                where start_time=? and end_time=?";

        db()->query($sql, array($message["start"], $message["end"]));
        echo "Запись бронирования удалена ";
        log_in_file("Запись бронирования удалена ");
    }

    private function un_skip_booking($message): void
    {
        echo "un_skip_booking: ";
        log_in_file("un_skip_booking: ");
        $this->create_booking($message);
    }

    private function is_not_execute($message): bool
    {
        return self::is_not_for_this_app($message) || self::is_send_from_me($message);
    }

    private static function is_not_for_this_app($message): bool
    {
        if ($message["name_to"] == "*" || $message["name_to"] == \Config::service_name) {
            if (str_contains($message["action"], ":") && strlen($message["action"]) >= 2) {
                $group = explode(":", $message["action"])[0];
                $config_group = \Config::group;
                if ($group == $config_group || $group == "*" || $group == "") {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    private static function is_send_from_me($message): bool
    {
        if ($message["name_from"] == \Config::service_name) {
            return true;
        }
        return false;
    }
}
