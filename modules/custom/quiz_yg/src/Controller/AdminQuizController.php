<?php

namespace Drupal\quiz_yg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\quiz_yg\Entity\Quiz;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\{Link,Url};
/**
/**
 * Controller routines for AJAX example routes.
 */
class AdminQuizController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  
  protected function getModuleName() {
    return 'quiz_yg';
  }


  public function getAdminQuizResult(){
    $header=[
        'serial'=>$this->t('Serial'),
        'quiz'=>$this->t('Quiz'),
        'Author'=>$this->t('Attempt Date'),
      ];
    $header = [
      [
        'data' => $this->t('Serial')
      ],
      [
        'data' => $this->t('Quiz'),
        'field' => 'title',
        'sort' => 'asc'
      ],
      [
        'data' => $this->t('Author'),
        'field' => 'name',
        'sort' => 'asc'
      ],
      [
        'data' => $this->t('HighScore Result')
      ],
      [
        'data' => $this->t('View Results')
      ],
    ];
    $connection = \Drupal::database();
    $query = $connection->select('quiz_attempts', 'qa');
    $query->fields('qa',['quiz_id'])->distinct();
    $query->join('node_field_data','nt','nt.nid=qa.quiz_id');
    $query->addField("nt","title");
    $query->join('users_field_data','unt','unt.uid=nt.uid');
    $query->addField("unt","name");
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                        ->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                        ->limit(20);
    $results = $pager->execute()->fetchall();
    // dpm($results)
    $i=1;
    foreach ($results as $value) {
      $details=$this->QuizHighScoreDetails($value->quiz_id);
      $init = $details['time_taken'];
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
      $hruser=User::load($details['user_id']);
      $hrname=$hruser->getUsername();
      $marks=$details['pscore'];
      $ltitle="$hrname($marks%)($time_taken)";
      $rows[]=[
        'serial'=>$i++,
        'quiz'=>$value->title,
        'author'=>$value->name,
        // 'quiz'=>$quiz->title->value,
        // 'author'=>$quiz->getOwner()->getDisplayName(),
        'highscore'=>Link::fromTextAndUrl($ltitle, Url::fromRoute('quiz_yg.quiz_result_id', [
          'quiz_id' => $value->quiz_id,
          'attempt_id'=>$details['attempt_id']
        ])),
        'view'=> Link::fromTextAndUrl('Result', Url::fromRoute('quiz_yg.quiz_result', ['node' => $value->quiz_id])),
      ];
    }
    $test = \Drupal::request()->query->get('keys');
    $form['form'] = [
        '#type'  => 'form',
        '#method'=>'GET'
    ];


    $form['form']['title1'] = [
        '#type'          => 'textfield',
        '#title'         => 'Quiz',
        '#description' => "Contain by words",
        '#value' => 'sd'
    ];

    $form['form']['actions'] = [
        '#type'       => 'actions'
    ];

    $form['form']['actions']['submit'] = [
        '#type'  => 'submit',
        '#value' => $this->t('Filter')
    ];

    //place the table in the form
    $form['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'bd-contact-table',
      ) 
    );

    $element['form']=$form;

    $element['quiz_result'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows'=>$rows
    ];
    $element['pager'] = array(
      '#type' => 'pager'
    );
    
    $element['#cache']['max-age'] = 0;
    return $element;
  }

  function QuizHighScoreDetails($quiz_id){
    $conn = \Drupal::database();
    $current_user = \Drupal::currentUser();
    $query = $conn->select('quiz_attempts', 'qa');
    $query->condition('qa.quiz_id', $quiz_id);
    $query->fields('qa');
    $attempts=$query->execute()->fetchAllAssoc('attempt_id');
    $rows=[];
    $i=1;
    $aresult=[
      'attempt_id'=>0,
      'user_id'=>0,
      'pscore'=>0,
      'time_taken'=>0,
      'outofscore'=>0,
    ];

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
      $pscore=$tot/$out*100;
      if($pscore>$aresult['pscore']){
        $aresult=[
          'attempt_id'=>$attempt_id,
          'user_id'=>$value->user_id,
          'pscore'=>$pscore,
          'time_taken'=>$value->time_taken,
          'outofscore'=>$tot." / ".$out,
        ];
      }
      elseif($pscore==$aresult['pscore'] && $value->time_taken<$aresult['time_taken']){
        $aresult=[
          'attempt_id'=>$attempt_id,
          'user_id'=>$value->user_id,
          'pscore'=>$pscore,
          'time_taken'=>$value->time_taken,
          'outofscore'=>$tot." / ".$out,
        ];
      }
    }
    return $aresult;
  }
}
