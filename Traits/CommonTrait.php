<?php

namespace App\Traits;

use App\MasterAudio;
use App\Models\EILiteSession;
use App\Models\IBMPersonalityAPIResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait CommonTrait
{

    public function makeDateTime($date, $time)
    {

        $date = new \DateTime($date);
        $time = new \DateTime($time);

        $merge = new \DateTime($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));

        return $merge->format('Y-m-d H:i:s');
    }

    /**
     * @param $endTime timestamp
     * @param $currentTime timestamp
     * End time should be greater than current time
     * @return float
     */
    public function timeDifference($endTime, $currentTime)
    {
        $diff = $endTime - $currentTime;
        $time = ($diff/3600);

        return round($time, 0);
    }


    /**
     * get all company users from cross reference table
     * @return array|null|object
     */
    public function getCurrentCompanyUsers(){

        dd(Auth::user()->company->users);
        global $wpdb;

        $emp = Wpjb_Model_Company::current();
        if(is_null($emp)) {
            $id = null;
        } else {
            $id = $emp->id;
        }

        // get all company users ids
        $users = $wpdb->get_results( "SELECT user_id FROM company_users WHERE company_id = {$id}");
        $result = array_column($users, 'user_id');
        $users_ids = implode(',', $result);

        // get all users link with company
        $users = $wpdb->get_results( "SELECT u.*, r.role_id FROM {$wpdb->prefix}users as u left join model_has_roles as r on r.model_id = u.ID WHERE u.ID IN ({$users_ids})");
        return $users;
    }

    /**
     *  Calculate all 8andAbove score.
     *
     * @param data with required keys: objectId and objectName
     *
     * @return array
     */
    public function elev8a_score( $data )
    {

        $objectName = null;
        $objectId = null;
        $transcriptId = null;
        $score = [
            'elv8' => 0,
            'abve' => [
                'value' => '',
                'title' => '',
                'desc' => ''
            ]
        ];
        if ( isset( $data[ 'transcriptId' ] ) ) {
            $object = MasterAudio::where('transcript_id', $data[ 'transcriptId' ])->first();
            if ( empty( $object ) ) {
                return $score;
            }

            $objectId = $object->object_id;
            $objectName = $object->object_name;
        } else if ( isset( $data[ 'objectId' ] ) && isset( $data[ 'objectName' ] ) ) {
            $transcriptId = MasterAudio::where('object_id', $data[ 'objectId' ])->where('object_name', $data[ 'objectName' ])->first();

            $objectId = $data[ 'objectId' ];
            $objectName = $data[ 'objectName' ];
        } else if ( !isset( $data[ 'objectName' ] ) ) {
            $objectName = MasterAudio::where('transcript_id', $data[ 'transcriptId' ])->first();
        } else {
            $objectName = $data['objectName'];
        }

        if ( empty( $objectName ) ) {
            return $score;
        }


        $abveTraits = [
            'need_challenge' => true,
            'need_love' => true,
            'value_conservation' => true,
            'need_structure' => true
        ];

        $elv8Traits = null;

        $allTraits = include(dirname(__FILE__) . '/../../config/elev8_matrix.php');
        $elv8Traits = $allTraits['XX'];


        $fields =  array_keys( $elv8Traits );

        if ( isset( $data[ 'personalityData' ] ) && is_array( $data[ 'personalityData' ] ) ) {
            $personalityData = $data[ 'personalityData' ];
        } else {
            $personalityData = IBMPersonalityAPIResult::where('transcript_id', $transcriptId)->whereIn('trait_id', $fields)->get();
        }

        if ( !is_array( $personalityData ) ) {
            return $score;
        }

        $max = 0;
        $abveResult = '';
        $elv8Score = 0;
        foreach ( $personalityData as $x ) {
            $x = (object)$x;
            $r = (float)$x->raw_score;
            $p = (float)$x->percentile;

            // Calculate abve score
            if ( isset( $abveTraits[ $x->trait_id ] ) ) {
                if ( $r > $max ) {
                    $max = $r;
                    $abveResult = $x->trait_id;
                }
            }

            if ( isset( $elv8Traits[ $x->trait_id ] ) ) {
                $score = 0;
                $k = $p;
                $i = 0;

                if ( $elv8Traits[ $x->trait_id ][ 0 ] === 'r' ) {
                    $k = $r;
                    $i = 1;
                }

                $k *= 100;
                for ( $j = $i; $j < count( $elv8Traits[ $x->trait_id ] ); $j++ ) {
                    if ( $k <= $elv8Traits[ $x->trait_id ][ $j ][ 0 ] ) {
                        $score = $elv8Traits[ $x->trait_id ][ $j ][ 1 ];
                        break;
                    }
                }

                $elv8Score += ( $score + ( intval( $k ) % 10 ) / 10 );
            }
        }

        $elv8Score = round( $elv8Score / count( $elv8Traits ), 2 );

        if ( $elv8Score >= 10 ) {
            $elv8Score = $elv8Score - .6;
        }

        $abveDefinition = [
            'need_challenge' => [
                'value' => 'A',
                'title' => 'Assertive',
                'desc' => 'Bottom line organizer; change agent'
            ],

            'need_love' => [
                'value' => 'B',
                'title' => 'Bellwether',
                'desc' => 'Instinctive communicators; influencers'
            ],

            'value_conservation' => [
                'value' => 'V',
                'title' => 'Veritable',
                'desc' => 'Reliable and dependable; real and genuine'
            ],

            'need_structure' => [
                'value' => 'E',
                'title' => 'Exactness',
                'desc' => 'Anchor of reality; analytical'
            ],

            '' => $score[ 'abve' ]
        ];

        return [
            'elv8' => $elv8Score,
            'abve' => $abveDefinition[ $abveResult ]
        ];
    }


}