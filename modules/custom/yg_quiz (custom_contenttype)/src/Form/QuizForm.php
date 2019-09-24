<?php

namespace Drupal\yg_quiz\Form;

use Drupal\Core\Form\{FormBase,FormStateInterface};
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\yg_quiz\Controller\QuizFormController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\{Link,Url};
use Drupal\Component\Utility\Timer;

class QuizForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_form';
  } 
  public function getTitle() {
    return 'Quiz Test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$node='') {
    $form['#tree'] = TRUE;
    $current_user = \Drupal::currentUser();
    Timer::start($current_user->id());
    $qcon=new QuizFormController();
    $quiz=Node::load($node)->toArray();
    $form['timer']=[
      '#markup'=>"<div id='timer'></div>",
    ];
    foreach ($quiz['field_questions'] as $key => $value) {
      $pid=$value['target_id'];
      $paragraph = Paragraph::load($pid);
      $ptype = $paragraph->getType();
      if($ptype=='multichoice')
        $form['question'][$key]=$qcon->getMultichoiceForm($paragraph);
      elseif($ptype=='matching')
        $form['question'][$key]=$qcon->getMatchingForm($paragraph);
      elseif($ptype=='t_or_f')
        $form['question'][$key]=$qcon->getTorFForm($paragraph);
    }
    $form['quiz_id'] = [
      '#type' => 'hidden',
      '#value' => $node,
    ];
    // Button to add more names.
    // $form['node'] = [
    //   '#markup' => $this->t('Node Id: @nid',['@nid'=>$node]),
    // ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['#attached']['library'][] = 'yg_quiz/yg_quiz_timer';

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    Timer::stop($current_user->id());
    $taken_time=Timer::read($current_user->id());
    $values = $form_state->getValues();
    $current_user = \Drupal::currentUser();
    $qcon=new QuizFormController();
    $conn = \Drupal::database();
    $attempt = Node::create([
      'type'        => 'quiz_attempt',
      'title'       => $current_user->getAccountName()." Attempt",
      'field_quiz_id' => [
        'target_id' => $values['quiz_id'],
      ],
      'field_user_id' => [
        'target_id' => $current_user->id(),
      ],
      'field_time_taken' =>  floor($taken_time/10),
    ]);
    $attempt->save();
    $score=0;
    $tot=0;
    $attempt_id=$attempt->id();
    foreach ($values['question'] as $key => $value) {
      $checkanswer=FALSE;
      $pid=$value['question_pid'];
      if(isset($value['answer']))
      $answer=$value['answer'];
      if($value['question_type']=='multichoice')
        $checkanswer=$qcon->checkMultichoiceAnswer($pid,$answer);
      elseif($value['question_type']=='t_or_f')
        $checkanswer=$qcon->checkTorFAnswer($pid,$answer);
      elseif($value['question_type']=='matching'){
        $matching_questions=$value['matching_questions'];
        foreach ($matching_questions as $pid => $mvalue) {
          $checkanswer=$qcon->checkMatchingAnswer($pid,$mvalue['match_answer']);
          $answer_result=Node::create([
            'type'        => 'quiz_result',
            'title'       =>  $current_user->getAccountName()." Result",
            'field_attempt_id'=> [
              'target_id' => $attempt_id,
            ],
            'field_question_id'=>$pid,
            'field_question_no'=>$key+1,
            'field_correct_answer'=>$checkanswer['correct_answer'],
            'field_user_answer'=>$mvalue['match_answer'],
            'field_score'=>$checkanswer['correct'],
            'field_correct'=>$checkanswer['correct'],
            'field_quiz_id' => [
              'target_id' => $values['quiz_id'],
            ],
            'field_user_id' => [
              'target_id' => $current_user->id(),
            ]
          ]);
          $answer_result->save();
          $score+=$checkanswer['correct'];
          $tot++;
        }
      }
      if($value['question_type']!='matching'){
        $answer_result=Node::create([
            'type'        => 'quiz_result',
            'title'       =>  $current_user->getAccountName()." Result",
            'field_attempt_id'=> [
              'target_id' => $attempt_id,
            ],
            'field_question_id'=>$value['question_pid'],
            'field_question_no'=>$key+1,
            'field_correct_answer'=>$checkanswer['correct_answer'],
            'field_user_answer'=>$answer,
            'field_score'=>$checkanswer['correct'],
            'field_correct'=>$checkanswer['correct'],
            'field_quiz_id' => [
              'target_id' => $values['quiz_id'],
            ],
            'field_user_id' => [
              'target_id' => $current_user->id(),
            ]
          ]);
          $answer_result->save();
          $score+=$checkanswer['correct'];
          $tot++;
      }
    }
    $quiz_id=$values['quiz_id'];
    $attempt->set('field_score',$score);
    $attempt->set('field_total',$tot);
    $attempt->save();
    $url = Url::fromRoute('yg_quiz.quiz_result_id', [
      'quiz_id' => $quiz_id,
      'attempt_id'=>$attempt_id
    ]);
    $redirectResponse=new RedirectResponse($url->toString());
    $redirectResponse->send();
    return FALSE; 
  }
}