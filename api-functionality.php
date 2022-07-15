<?php

namespace App\Controller\Apis;

use App\Controller\AppController;


use Cake\Event\EventInterface;
use Cake\Http\Cookie\Cookie;
use Cake\I18n\I18n;
use DateTime;
/**
 * Projects Controller
 *
 * @property \App\Model\Table\ProjectsTable $Projects
 * @method \App\Model\Entity\Project[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsController extends AppController
{
    /**
     * Fetch current daily work logs/tasks
     * @returns object
     */
    public function getTodayWorkLogs()
    {
        $response = array();
        $this->request->allowMethod(['post', 'put']);
        if ($this->request->is('post')) {
            $input = file_get_contents('php://input');
            $requestData = json_decode($input, true);
            if(empty($requestData['token'])){
                $response["status"] = false;
                $response["message"] =  __("TokenFieldEmpty");
                $response["data"] = [];
                goto label;
            }else if(empty($requestData['project_id'])){
                $response["status"] = false;
                $response["message"] =  'project_id '.__("FeildEmpty");
                $response["data"] = [];
                goto label;
            }else if(empty($requestData['entry_date'])){
                $response["status"] = false;
                $response["message"] =  'entry_date '.__("FeildEmpty");
                $response["data"] = [];
                goto label;
            }elseif(empty($requestData['user_id']))
            {
                $currentUser = $this->WorkLogs->Users->findByToken($requestData['token'])->first();
                $work_log_started = $this->WorkLogs->find('all',[
                    'fields'=>[
                        'id',
                        'project_id',
                        'user_id',
                        'started_at',
                        'ended_at'
                    ]
                ] )
            ->where([
                'user_id'=>$currentUser->id,
                'DATE(started_at)'=>$requestData['entry_date'],
                'WorkLogs.project_id'=>$requestData['project_id']
                ])->toArray();
                $temp_storage = $work_logs = array();
                    
                foreach ($work_log_started as $work_log){
                    $temp_storage = $this->ProjectActivities->find('all', [
                            'fields'=>[
                                'id',
                                'comment',
                                'status'
                            ]
                        ])->where(['work_log_id'=>$work_log['id']]);

                    if($work_log['started_at']){
                        $work_log['started_at'] = $work_log['started_at']->format('Y-m-d H:i:s');
                    }
                    if($work_log['ended_at']){
                        $work_log['ended_at'] = $work_log['ended_at']->format('Y-m-d H:i:s');
                    }
                    
                    $work_log['project_activity'] = $temp_storage;

                    $work_logs[] = $work_log;
                }
            
                if($work_logs){
                    $response["status"] = true;
                    $response["message"] =  __("ListRetrivedSuccess");
                    $response["data"] = $work_logs;
                    goto label;
                }else{
                    $response["status"] = false;
                    $response["message"] =  __("NoDataFoundText");
                    $response["data"] = [];
                    goto label;
                }
            }
            else{
                $currentUser = $this->WorkLogs->Users->findByToken($requestData['token'])->first();
                if (!$currentUser) {
                    $response["status"] = false;
                    $response["message"] =  __("InvalidToken");
                    $response["errorcode"] = 401;
                    $response["data"] = [];
                    goto label;
                }else{
                   
                    $work_log_started = $this->WorkLogs->find('all',[
                                'fields'=>[
                                    'id',
                                    'project_id',
                                    'user_id',
                                    'started_at',
                                    'ended_at'
                                ]
                            ] )
                        ->where([
                            'user_id'=>$requestData['user_id'],
                            'DATE(started_at)'=>$requestData['entry_date'],
                            'WorkLogs.project_id'=>$requestData['project_id']
                            ])->toArray();

                    $temp_storage = $work_logs = array();
                    
                    foreach ($work_log_started as $work_log){
                        $temp_storage = $this->ProjectActivities->find('all', [
                                'fields'=>[
                                    'id',
                                    'comment',
                                    'status'
                                ]
                            ])->where(['work_log_id'=>$work_log['id']]);

                        if($work_log['started_at']){
                            $work_log['started_at'] = $work_log['started_at']->format('Y-m-d H:i:s');
                        }
                        if($work_log['ended_at']){
                            $work_log['ended_at'] = $work_log['ended_at']->format('Y-m-d H:i:s');
                        }
                        
                        $work_log['project_activity'] = $temp_storage;

                        $work_logs[] = $work_log;
                    }
                
                    if($work_logs){
                        $response["status"] = true;
                        $response["message"] =  __("ListRetrivedSuccess");
                        $response["data"] = $work_logs;
                        goto label;
                    }else{
                        $response["status"] = false;
                        $response["message"] =  __("NoDataFoundText");
                        $response["data"] = [];
                        goto label;
                    }
                }
            }
        }
        
        label:
        
        $this->set (compact('response'));
        $this->viewBuilder()->setOption('serialize', 'response');

    }
    /**
     * Translate Post/put request with deepl api
     * @returns string
     */
    public function translateLanguage(){
    $this->request->allowMethod(['post', 'put']);
        if ($this->request->is('post')) {
            $input = file_get_contents('php://input');
            $requestData = json_decode($input, true);
            // print_r($requestData);die;
            if(empty($requestData)){
                    $response["status"] = false;
                    $response["message"] =  __("EnterAllInformation");
                    $response["data"] = (object)[];
                    goto label;
            }else{
                if( !isset($requestData['text']) || empty($requestData['text']) ){
                    $response["status"] = false;
                    $response["message"] ='text '._('FeildEmpty') ;
                    $response["data"] = (object)[];
                    goto label;
                }elseif (!isset($requestData['target_lang']) || empty($requestData['target_lang'])) {
                    $response["status"] = false;
                    $response["message"] ='target_lang '._('FeildEmpty') ;
                    $response["data"] = (object)[];
                    goto label;
                }else{
                    $curl_url = "https://api-free.deepl.com/v2/translate?auth_key=AUTH_TOKEN";
                    $curl_url  = $curl_url.'&text='.$requestData['text'].'&target_lang='.$requestData['target_lang'];        
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => $curl_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'authtoken: AUTH_TOKEN'
                    ),
                    ));

                    $cu_response = curl_exec($curl);

                    curl_close($curl);

                    $response["status"] = true;
                    $response["message"] =_('Success') ;
                    $response["data"] = json_decode($cu_response);
                    goto label;
                }
            }

        }
        label: 
        $this->set(compact('response'));
        $this->viewBuilder()->setOption('serialize', 'response');
    }
}
?>