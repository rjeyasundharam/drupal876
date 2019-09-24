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
      // dpm($ptype);
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
    $query = $conn->insert('quiz_attempts')
              ->fields(['quiz_id', 'user_id', 'created','time_taken'])
              ->values([
                'quiz_id' => $values['quiz_id'],
                'user_id' => $current_user->id(),
                'created' => REQUEST_TIME,
                'time_taken'=>$taken_time/10,
              ]);
    $attempt_id = $query->execute();
    $query = $conn->insert('quiz_result')
      ->fields(['attempt_id', 'quiz_id', 'question_no','question_id','user_id','answer','correct_answer','correct','created','score']);
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
          $query->values([
                    'attempt_id' => $attempt_id,
                    'quiz_id' => $values['quiz_id'],
                    'question_no' => $key+1,
                    'question_id' => $pid,
                    'user_id' => $current_user->id(),
                    'answer' => $mvalue['match_answer'],
                    'correct_answer'=>$checkanswer['correct_answer'],
                    'correct'=>$checkanswer['correct'],
                    'created' => REQUEST_TIME,
                    'score' => $checkanswer['correct']
                  ]);
        }
      }
      if($value['question_type']!='matching'){
        $query->values([
          'attempt_id' => $attempt_id,
          'quiz_id' => $values['quiz_id'],
          'question_no' => $key+1,
          'question_id' => $value['question_pid'],
          'user_id' => $current_user->id(),
          'answer' => $answer,
          'correct_answer'=>$checkanswer['correct_answer'],
          'correct'=>$checkanswer['correct'],
          'created' => REQUEST_TIME,
          'score' => $checkanswer['correct']
        ]);
      }
    }
    $result = $query->execute();
    $quiz_id=$values['quiz_id'];
    $url = Url::fromRoute('yg_quiz.quiz_result_id', [
      'quiz_id' => $quiz_id,
      'attempt_id'=>$attempt_id
    ]);
    $redirectResponse=new RedirectResponse($url->toString());
    $redirectResponse->send();
    return FALSE; 
  }
}