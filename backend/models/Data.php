<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\User;
use frontend\models\Meeting;
use frontend\models\UserPlace;
use backend\models\UserData;
use backend\models\HistoricalData;

/**
 * This is the model class for table "friend".
 *
 */
class Data extends Model
{

  public static function recalc() {
    UserData::reset();
    HistoricalData::reset();
    $after = mktime(0, 0, 0, 2, 15, 2016);
    $since = mktime(0, 0, 0, 4, 1, 2016);
    while ($since < time()) {
      UserData::calculate($since,$after);
      HistoricalData::calculate($since,$after);
      // increment a day
      $since+=24*60*60;
    }
  }
  public static function getRealTimeData() {
    $data = new \stdClass();

    $data->meetings =  new ActiveDataProvider([
      'query' => Meeting::find()
      ->select(['status,COUNT(*) AS dataCount'])
      //->where('approved = 1')
      ->groupBy(['status']),
      'pagination' => [
      'pageSize' => 20,
      ],
      ]);

    $data->users = new ActiveDataProvider([
      'query' => User::find()
      ->select(['status,COUNT(*) AS dataCount'])
      ->groupBy(['status']),
      'pagination' => [
      'pageSize' => 20,
      ],
      ]);

    $data->userPlaces = new ActiveDataProvider([
      'query' => UserPlace::find()
      ->select(['user_id,count(*) AS dataCount'])
      ->groupBy(['user_id'])
      ->limit(5),
      'pagination' => false,
      ]);

    // calculate average # of places per user
    $user_places = UserPlace::find()
      ->select(['user_id,count(*) AS dataCount'])
      ->groupBy(['user_id'])
      ->all();

      $totalUsers = 0;
      $totalPlaces = 0;
      foreach ($user_places as $up) {
        $totalUsers+=1;
        $totalPlaces+=$up->dataCount;
      }
      $data->avgUserPlaces = $totalPlaces / $totalUsers;

      return $data;
  }

}
