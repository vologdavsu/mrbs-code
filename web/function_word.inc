<?php
namespace MRBS;

use PhpOffice\PhpWord\TemplateProcessor;


/**
 * @param $res
 * @return void
 */
function create_docx($res): void
{
  $file_name = 'event_hall_document_filled.docx';
  $document = new TemplateProcessor('event_hall_document.docx');
  $document->setValue('creator_full_name', session()->getCurrentUser()->display_name);
  $document->setValue('creator_post', '04.10.2014');
  $document->setValue('creator_institute', 'Никоненко');
  $document->setValue('creator_email', session()->getCurrentUser()->email);
  $document->setValue('location', $res['room_name'].', '.$res['area_name']);
  $document->setValue('description', $res['name']);
  $document->setValue('date_start', secondsToTime($res['start_time']));
  $document->setValue('date_end', secondsToTime($res['end_time']));
  $document->setValue('multimedia', $res['description']);
  $document->setValue('creator_phone', 'Васильевич');
  $document->saveAs('filled_documents/'.$file_name);

  header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: private",false);
  header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document;");
  header("Content-Disposition: attachment; filename=\"".$file_name."\";" );
  header("Content-Length: " . filesize('filled_documents/'.$file_name));
  header("Content-Transfer-Encoding: binary");
  ob_clean();
  flush();
  readfile( 'filled_documents/'.$file_name);
}

/**
 * @param $seconds
 * @return string
 */
function secondsToTime($seconds): string
{
  return date("d.m.Y, H:i", $seconds);
}
