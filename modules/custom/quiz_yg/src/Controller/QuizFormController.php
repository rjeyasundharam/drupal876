<?php

namespace Drupal\quiz_yg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quiz_yg\Entity\Quiz;
/**
 * Controller routines for AJAX example routes.
 */
class QuizFormController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  
  protected function getModuleName() {
    return 'quiz_yg';
  }


  public function getMultichoiceForm(Quiz $quiz) {
    $question=$quiz->field_question->value;
    $answers=$quiz->field_manswer;
    $answer=[];
    $correct=[];
    foreach ($answers as $key => $value) {
      $id=$value->entity->id->value;
      $canswer=$value->entity->field_answer->value;
      $correct[$id]=$value->entity->field_correct->value;
      $answer[$canswer]=$value->entity->field_answer->value;
    }
    $element['question_type']=[
      '#type'=>'hidden',
      '#value'=>'multichoice'
    ];
    $element['question_pid']=[
      '#type'=>'hidden',
      '#value'=>$quiz->id->value
    ];
    $element['question']=[
      '#markup'=>$question,
    ];
    $element['answer']=[
      '#type' => 'radios',
      '#title' => $this->t(''),
      '#options' => $answer,
    ];
    return $element;
  }

  public function getMatchingForm(Quiz $quiz) {
    $moption=$quiz->field_match_options;
    $manswer[]='';
    foreach ($moption as $key => $value) {
      $id=$value->entity->id->value;
      $canswer=$value->entity->field_answer->value;
      $question[$id]=$value->entity->field_question->value;
      $manswer[$canswer]=$value->entity->field_answer->value;
    }
    $matching_questions=[];
    foreach ($moption as $key => $value) {
      $id=$value->entity->id->value;
      $matching_questions[$id]=[
        'question'=>[
          '#markup' => $value->entity->field_question->value,
        ],
        'match_answer'=>[
          '#type' => 'select',
          '#title' => $this->t(''),
          '#options' => $manswer,
        ]
      ];
    }
    $element['question_type']=[
      '#type'=>'hidden',
      '#value'=>'matching'
    ];
    $element['question_pid']=[
      '#type'=>'hidden',
      '#value'=>$quiz->id->value
    ];
    $element['matching_questions'] = [
      '#type' => 'table',
      '#caption' => $this->t('Match the Following'),
      '#header' => [
        $this->t('Question'),
        $this->t('Match answer'),
      ],
    ];
    foreach ($matching_questions as $key => $value) {
      $element['matching_questions'][$key]=$value;
    }
    return $element;
  }

  public function getTorFForm(Quiz $quiz) {
    $question=$quiz->field_question->value;
    $element['question_type']=[
      '#type'=>'hidden',
      '#value'=>'t_or_f'
    ];
    $element['question_pid']=[
      '#type'=>'hidden',
      '#value'=>$quiz->id->value
    ];
    $element['question']=[
      '#markup'=>$question,
    ];
    $element['answer']=[
      '#type' => 'radios',
      '#title' => $this->t(''),
      '#options' => [
        1 => $this->t('True'),
        0 => $this->t('False'),
      ],
    ];
    return $element;
  }

  public function checkMultichoiceAnswer($pid,$answer) {
    $quiz=Quiz::load($pid);
    $answers=$quiz->field_manswer;
    foreach ($answers as $key => $value) {
      $correct=$value->entity->field_correct->value;
      $panswer=$value->entity->field_answer->value;
      $result['correct_answer']=$panswer;
      if($correct==1){
        if($panswer==$answer){
          $result['correct']=1;
          break;
        }
      }
      else{
        $result['correct']=0;
      }

    }
    return $result;
  }
  public function checkTorFAnswer($pid,$answer) {
    $quiz=Quiz::load($pid);
    $tfanswer=$quiz->field_torf_answer->value;
    $result['correct_answer']=$tfanswer;
    if($tfanswer==$answer){
      $result['correct']=1;
    }
    else{
      $result['correct']=0;
    }
    return $result;
  }
  public function checkMatchingAnswer($pid,$answer) {
    $quiz=Quiz::load($pid);
    $manswer=$quiz->field_answer->value;
    $result['correct_answer']=$manswer;
    if($manswer==$answer){
      $result['correct']=1;
    }
    else{
      $result['correct']=0;
    }
    return $result;
  }
}
