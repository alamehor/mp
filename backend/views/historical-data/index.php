<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\HistoricalDataSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('backend', 'Historical Statistics');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="historical-data-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

<?php
Pjax::begin(); ?>    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
            [
              'label'=>'Date',
                'attribute' => 'date',
                'format' => ['date', 'php:m/d/y'],
              ],
              'count_users',
              [
              'label'=>'% Mtg Org',
                'attribute' => 'percent_own_meeting',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f%%", 100*$model->percent_own_meeting).'</div>';
                    },
            ],
            [
              'label'=>'% Mtg Org L30',
                'attribute' => 'percent_own_meeting_last30',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f%%", 100*$model->percent_own_meeting_last30).'</div>';
                    },
            ],
            [
              'label'=>'% Prtcpnt',
                'attribute' => 'percent_participant',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f%%", 100*$model->percent_participant).'</div>';
                    },
            ],
            [
              'label'=>'% Prtcpnt L30',
                'attribute' => 'percent_participant_last30',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f%%", 100*$model->percent_participant_last30).'</div>';
                    },
            ],
            [
              'label'=>'% Inv then Org',
                'attribute' => 'percent_invited_own_meeting',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f%%", 100*$model->percent_invited_own_meeting).'</div>';
                    },
            ],
            'count_meetings_completed',
            'count_meetings_planning',
            [
                'attribute' => 'average_meetings',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f", $model->average_meetings).'</div>';
                    },
            ],
            [
                'attribute' => 'average_friends',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f", $model->average_friends).'</div>';
                    },
            ],
            [
                'attribute' => 'average_places',
                'format' => 'raw',
                'value' => function ($model) {
                            return '<div>'.sprintf("%.1f", $model->average_places).'</div>';
                    },
            ],             
             'count_places',
             'source_google',
             'source_facebook',
             'source_linkedin',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
<?php Pjax::end(); ?></div>
