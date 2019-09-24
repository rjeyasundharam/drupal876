<?php

namespace Drupal\yg_quiz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\{Link,Url};
/**
 * Controller routines for AJAX example routes.
 */
class QuizResultController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  
  protected function getModuleName() {
    return 'yg_quiz';
  }
  public function getUserAllQuizResult($node) {
    $quiz_id=$node;
    $conn = \Drupal::database();
    $current_user = \Drupal::currentUser();
    $user_roles=$current_user->getRoles();
    if(in_array("administrator", $user_roles)){
      $query = $conn->select('quiz_attempts', 'qa');
      $query->condition('qa.quiz_id', $quiz_id);
      $query->fields('qa');
      $attempts=$query->execute()->fetchAllAssoc('attempt_id');
      $rows=[];
      $i=1;
      foreach ($attempts as $key => $value) {
        $attempt_id=$value->attempt_id;
        $query = $conn->select('quiz_result', 'qr');
        $query->condition('qr.quiz_id', $quiz_id);
        $query->condition('qr.attempt_id', $attempt_id);
        $query->fields('qr');
        $query->orderBy('qr.question_no');
        $qresult=$query->execute()->fetchAllAssoc('result_id');
        $result=[];
        $tot=$out=0;
        foreach ($qresult as $key1 => $value1) {
         $tot=$tot+$value1->score;
         $out++;
        }
        $score=$tot." / ".$out;
        $init = $value->time_taken;
        $hours = floor($init / 3600);
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        $time_taken='';
        if($hours>0)
          $time_taken.=$hours." H";
        if($minutes>0)
          $time_taken.=$minutes." Min";
        if($seconds>0)
          $time_taken.=$seconds." Sec";


        $rows[]=[
          'serial'=>$i++,
          'attempt_date'=>format_date($value->created),
          'score'=>$score,
          'time_taken'=>$time_taken,
          'view'=> Link::fromTextAndUrl('View', Url::fromUserInput("/node/$quiz_id/quiz-result/$attempt_id")),
        ];
      }
      $element['quiz_result'] = [
        '#type' => 'table',
        '#header' => [
          'serial'=>$this->t('Serial'),
          'attempt_date'=>$this->t('Attempt Date'),
          'score'=>$this->t('score'),
          'time_taken'=>$this->t('Time Taken'),
          'view'=> $this->t('Result'),
        ],
        '#rows'=>$rows
      ];

    }
    else{
      $query = $conn->select('quiz_attempts', 'qa');
      $query->condition('qa.quiz_id', $quiz_id);
      $query->condition('qa.user_id', $current_user->id());
      $query->fields('qa');
      $attempts=$query->execute()->fetchAllAssoc('attempt_id');
      $rows=[];
      $i=1;
      foreach ($attempts as $key => $value) {
        $attempt_id=$value->attempt_id;
        $query = $conn->select('quiz_result', 'qr');
        $query->condition('qr.quiz_id', $quiz_id);
        $query->condition('qr.user_id', $current_user->id());
        $query->condition('qr.attempt_id', $attempt_id);
        $query->fields('qr');
        $query->orderBy('qr.question_no');
        $qresult=$query->execute()->fetchAllAssoc('result_id');
        $result=[];
        $tot=$out=0;
        foreach ($qresult as $key1 => $value1) {
         $tot=$tot+$value1->score;
         $out++;
        }
        $score=$tot." / ".$out;
        $init = $value->time_taken;
        $hours = floor($init / 3600);
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        $time_taken='';
        if($hours>0)
          $time_taken.=$hours." H";
        if($minutes>0)
          $time_taken.=$minutes." Min";
        if($seconds>0)
          $time_taken.=$seconds." Sec";


        $rows[]=[
          'serial'=>$i++,
          'attempt_date'=>format_date($value->created),
          'score'=>$score,
          'time_taken'=>$time_taken,
          'view'=> Link::fromTextAndUrl('View', Url::fromUserInput("/node/$quiz_id/quiz-result/$attempt_id")),
        ];
      }
      $element['quiz_result'] = [
        '#type' => 'table',
        '#header' => [
          'serial'=>$this->t('Serial'),
          'attempt_date'=>$this->t('Attempt Date'),
          'score'=>$this->t('score'),
          'time_taken'=>$this->t('Time Taken'),
          'view'=> $this->t('Result'),
        ],
        '#rows'=>$rows
      ];
    }
   
    return $element;
  }
  public function getUserQuizResult($quiz_id,$attempt_id) {
    $conn = \Drupal::database();
    $current_user = \Drupal::currentUser();
    $query = $conn->select('quiz_result', 'qr');
    $query->condition('qr.quiz_id', $quiz_id);
    $query->condition('qr.user_id', $current_user->id());
    $query->condition('qr.attempt_id', $attempt_id);
    $query->fields('qr');
    $query->orderBy('qr.question_no');
    $qresult=$query->execute()->fetchAllAssoc('result_id');
    $result=[];
    $element['title']=[
      '#markup'=>$this->t('<h1>Quiz result</h1>'),
    ];
    $tot=$out=0;
    foreach ($qresult as $key => $value) {
     $result[$value->question_no][]=[
      'result_id'=>$value->result_id,
      'attempt_id'=>$value->attempt_id,
      'quiz_id'=>$value->quiz_id,
      'question_no'=>$value->question_no,
      'question_id'=>$value->question_id,
      'user_id'=>$value->user_id,
      'answer'=>$value->answer,
      'correct_answer'=>$value->correct_answer,
      'correct'=>$value->correct,
      'created'=>$value->created,
      'score'=>$value->score,
     ];
     $created=$value->created;
     $tot=$tot+$value->score;
     $out++;
    }
    $element['scores']=[
      '#markup' => "<p>".$this->t("You had scored @point out of @out",[
        "@point"=>$tot,
        "@out" => $out
      ])."</p>",
    ];
    $element['attempted']=[
      '#markup' => "<p>".$this->t("You had attempt this quiz at @time",["@time"=>format_date($created)])."</p>",
    ];
    foreach ($result as $key => $value) {
      foreach ($value as $key1 => $value1) {
        $question=Paragraph::load($value1['question_id']);
        $qtype=$question->getType();
        if($qtype=='multichoice'){
          $element['answers'][]=$this->getMultichoiceResult($value1);
        }
        elseif($qtype=='matching_anwers'){
          $element['answers'][]=$this->getMatchingResult($value);
          break;
        }
        elseif($qtype=='t_or_f'){
          $element['answers'][]=$this->getTorFResult($value1);
        }

      }
    }
    return $element;
  }

  private function getMultichoiceResult(array $result){
    global $base_url;
    $paragraph=Paragraph::load($result['question_id']);
    $question=$paragraph->field_question->value;
    $answers=$paragraph->field_answers;
    $ranswer=$result['answer'];
    $rows=[];
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('yg_quiz')->getPath();
    $yanswer=$base_url."/".$module_path.'/images/arrow-right.png';
    $wrong=$base_url."/".$module_path.'/images/wrong.png';
    $acorrect=$base_url."/".$module_path.'/images/correct.png';
    foreach ($answers as $key => $value) {
      $result=[];
      $correct=$value->entity->field_correct->value;
      $panswer=$value->entity->field_answer->value;
      $result['correct_answer']=$panswer;

      if($panswer==$ranswer){
        $result['yanswer']=$this->t("<img src='$yanswer'>");
        if($correct==1){
          $result['correct']=1;
          $result['correctchoice']=$this->t("<img src='$acorrect'>");
          $result['acorrect']=$this->t("<img src='$acorrect'>");

        }
        else{
          $result['correct']=0;
          $result['acorrect']=$this->t("<img src='$wrong'>");
        }
      }
      else{
        $result['correct']=0;
        if($correct==1){
          $result['correctchoice']=$this->t("<img src='$acorrect'>");
        }
        else{
          $result['acorrect']='';//$this->t("<img src='$wrong'>");
        }
      }

      $rows[]=[
        'your_answer'=> $result['yanswer'],
        'choice'=> $panswer,
        'correct'=> $result['correctchoice'],
        'score'=>$result['correct'],
        'correct_answer'=> $result['acorrect']
      ];

    }
    $element['matching_question']=[
        '#markup'=>$question
    ];
    $element['matching_choices'] = [
      '#type' => 'table',
      '#header' => [
        'your_answer'=> $this->t('Your answer'),
        'choice'=> $this->t('Choice'),
        'correct'=> $this->t('Correct?'),
        'score'=>$this->t('Score'),
        'correct_answer'=> $this->t('Correct answer'),
      ],
      '#rows'=>$rows
    ];
    return $element;
  }
  private function getMatchingResult(array $matches){
    global $base_url;
    $rows=[];
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('yg_quiz')->getPath();
    $yanswer=$base_url."/".$module_path.'/images/arrow-right.png';
    $wrong=$base_url."/".$module_path.'/images/wrong.png';
    $acorrect=$base_url."/".$module_path.'/images/correct.png';

    $question=$paragraph->field_question->value;
    $moption=$paragraph->field_match_options;
    foreach ($matches as $key => $value) {
      $paragraph=Paragraph::load($value['question_id']);
      $yanswer=$value['answer'];
      $manswer=$paragraph->field_manswer->value;
      $choice=$paragraph->field_question->value;
      if($manswer==$yanswer){
        $result['correct']=1;
        $result['correct_image']=$this->t("<img src='$acorrect'>");
        $result['correct_answer']=$manswer;       
      }
      else{
        $result['correct']=0;
        $result['correct_image']=$this->t("<img src='$wrong'>");
      }
      $rows[]=[
        'your_answer'=> $yanswer,
        'choice'=> $this->t("$choice"),
        'correct'=> $result['correct_image'],
        'score'=>$result['correct'],
        'correct_answer'=> $manswer,
      ];
    }

    $element['matching_question']=[
        '#markup'=>$this->t('Match the following'),
    ];
    $element['matching_choices'] = [
      '#type' => 'table',
      '#header' => [
        'your_answer'=> $this->t('Your answer'),
        'choice'=> $this->t('Choice'),
        'correct'=> $this->t('Correct?'),
        'score'=>$this->t('score'),
        'correct_answer'=> $this->t('Correct answer'),
      ],
      '#rows'=>$rows
    ];
    return $element;
  }
  private function getTorFResult(array $result){
    global $base_url;
    $paragraph=Paragraph::load($result['question_id']);
    $question=$paragraph->field_question->value;
    $yanswer=$result['answer'];
    $rows=[];
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('yg_quiz')->getPath();
    $wrong=$base_url."/".$module_path.'/images/wrong.png';
    $acorrect=$base_url."/".$module_path.'/images/correct.png';
    $tfanswer=$paragraph->field_torf_answer->value;
    $result['correct_answer']=$yanswer;
    if($tfanswer==$yanswer){
      $result['correct']=1;
      $result['correctchoice']=$this->t("<img src='$acorrect'>");
    }
    else{
      $result['correct']=0;
      $result['correctchoice']=$this->t("<img src='$wrong'>");
    }
      $rows[]=[
        'your_answer'=> $yanswer ? "TRUE":"FALSE" ,
        'correct'=> $result['correctchoice'],
        'score'=>$result['correct'],
        'correct_answer'=> $tfanswer ? "TRUE":"FALSE",
      ];
    $element['matching_question']=[
        '#markup'=>$question
    ];
    $element['matching_choices'] = [
      '#type' => 'table',
      '#header' => [
        'your_answer'=> $this->t('Your answer'),
        'correct'=> $this->t('Correct?'),
        'score'=>$this->t('score'),
        'correct_answer'=> $this->t('Correct answer'),
      ],
      '#rows'=>$rows
    ];
    return $element;
  }
}
