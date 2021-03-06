<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use dosamigos\google\maps\Map;
use dosamigos\google\maps\LatLng;
use dosamigos\google\maps\overlays\Marker;

/* @var $this yii\web\View */
/* @var $model frontend\models\Meeting */

$this->title = $model->getMeetingHeader();
$this->params['breadcrumbs'][] = ['label' => Yii::t('frontend', 'Meetings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="meeting-view">

  <div class="panel panel-default">
    <!-- Default panel contents -->
    <div class="panel-body">
      <div class="row">
        <div class="col-lg-6"></div>
        <div class="col-lg-6" >
          <div style="float:right;">
            <!--  to do - check meeting settings if participant can send/finalize -->
            <?php
            echo Html::a(Yii::t('frontend', 'Reschedule'), ['reschedule', 'id' => $model->id], ['id'=>'actionReschedule','class' => 'btn btn-default',
            'data-confirm' => Yii::t('frontend', 'Sorry, this feature is not yet available.')]);
            ?>
            <?php
            echo Html::a(Yii::t('frontend', 'Running Late'), ['late', 'id' => $model->id], ['id'=>'actionLate','class' => 'btn btn-default',
          'data-confirm' => Yii::t('frontend', 'Sorry, this feature is not yet available.')]);
            ?>
            <?php echo Html::a('', ['cancel', 'id' => $model->id],
           ['class' => 'btn btn-primary glyphicon glyphicon-remove-circle btn-danger',
           'title'=>Yii::t('frontend','Cancel'),
           'data-confirm' => Yii::t('frontend', 'Are you sure you want to cancel this meeting?')
           ]) ?>
          </div>
        </div>
      </div> <!-- end row -->
    </div> <!-- end head -->
  </div>

  <?php if ($isOwner) {
    echo $this->render('../participant/_panel', [
        'model'=>$model,
        'participantProvider' => $participantProvider,
    ]);
  }
   ?>

   <div class="panel panel-default">
     <!-- Default panel contents -->
     <div class="panel-heading">
       <div class="row">
         <div class="col-lg-9"><h4>What</h4></div>
         <div class="col-lg-3" ><div style="float:right;">

         </div>
       </div>
       </div>
     </div>
     <div class="panel-body">
       <?php echo Html::encode($this->title) ?>
     <?php echo $model->message.'&nbsp;'; ?>
     </div>
   </div>



    <div class="panel panel-default">
      <!-- Default panel contents -->
      <div class="panel-heading">
        <div class="row">
          <div class="col-lg-9"><h4><?= Yii::t('frontend','When') ?></h4><p><em>
          </div>
        </div>
      </div>
        <div class="panel-body">
          <p><?php echo $time; ?></p>

        </div>
      </div>


    <div class="panel panel-default">
      <div class="panel-heading">
        <div class="row">
          <div class="col-lg-12"><h4>Where</h4></div>
        </div>
      </div>
      <div class="panel-body">
    <?php
      if ($noPlace) { ?>
        <div class="col-lg-12">
      <?php
        // show conference contact info
        if (count($contacts)>0) {
          foreach ($contacts as $c) {

          ?>
          <p>
          <?php
            echo $contactTypes[$c['contact_type']].': '.$c['info'];
          ?>
        </p>
        <?php
          }
        } else {
          echo '<p>'.Yii::t('frontend','No contact information available for your meeting partner yet.').'</p>';
        }
        ?>
  </div>
  <?php
      } else {
        // show place and map
?>
  <div class="col-lg-6">
    <div class="place-view">
        <p><?php echo $place->name; ?></p>
        <p><?php echo Html::a($place->website, $place->website); ?></p>
        <p><?php echo $place->full_address; ?></p>

    </div>
    </div> <!-- end first col -->
    <div class="col-lg-6">
      <?php
      if ($gps!==false) {
        $coord = new LatLng(['lat' => $gps->lat, 'lng' => $gps->lng]);
        $map = new Map([
            'center' => $coord,
            'zoom' => 14,
            'width'=>300,
            'height'=>300,
        ]);
        $marker = new Marker([
            'position' => $coord,
            'title' => $place->name,
        ]);
        // Add marker to the map
        $map->addOverlay($marker);
        echo $map->display();
      } else {
        echo 'No location coordinates for this place could be found.';
      }
      ?>
    </div> <!-- end second col -->
    <?php
  }
   ?>
  </div> <!-- end panel body -->
</div> <!-- end panel -->

    <?php echo $this->render('../meeting-note/_panel', [
            'model'=>$model,
            'noteProvider' => $noteProvider,
        ]) ?>
</div>
