<?php
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 **/
namespace Marwa\Application\Jobs;
use Carbon\Carbon;
use Marwa\Application\Facades\DB;
use Marwa\Application\Jobs\Queue;
use Marwa\Application\Jobs\JobTrait;

use Exception;

class JobWorker
{
    use JobTrait;
    /**
     * [$queue description]
     *
     * @var array
     */
    var $queue=[];
    /**
     * [$db_pull_timer description]
     *
     * @var integer
     */
    var $db_pull_timer=10;
    /**
     * [$job_timer description]
     *
     * @var integer
     */
    var $job_timer=1;

    /**
     * [$maxAttempts description]
     *
     * @var integer
     */
    var $maxAttempts = 5;

    /**
     * [$config description]
     *
     * @var array
     */
    var $config = [];
    /**
     * [__construct description]
     */
    public function __construct()
    {
        //load configuration
        $this->config = app('config')->load('queue.php');

        //max attempts for failed queue
        if(array_key_exists('max_attempts', $this->config)) {
            $this->setMaxAttempts($this->config['max_attempts']);
        }

        //set db pull timer
        if(array_key_exists('db_pull_timer', $this->config)) {
            $this->setDbPullTimer($this->config['db_pull_timer']);
        }
        // set job process heartbeat timer
        if(array_key_exists('process_heartbeat', $this->config)) {
            $this->setJobTimer($this->config['process_heartbeat']);
        }
        $this->queue = new Queue();
    }


    /**
     * [addJobRunner description]
     *
     * @param [type] $clsName [description]
     */
    public function addJob(array $job)
    {
        if(empty($job)) {
            throw new Exception("Job Can not empty", 1);
        }
        if(!is_array($job)) {
            throw new Exception("Job must be array", 1);
        }
        if(!array_key_exists('handler', $job)) {
            throw new Exception("Job handler not found", 1);
        }

        $this->queue->enqueue($job);
    }

    /**
     * [setMaxAttempts description]
     *
     * @param int $attempts [description]
     */
    public function setMaxAttempts(int $attempts)
    {
        if($attempts > 0) {
            $this->maxAttempts = $attempts;
        }
        return $this;
    }

    /**
     * [setDbPullTimer description]
     *
     * @param int|integer $sec [description]
     */
    public function setDbPullTimer(int $sec=10)
    {
        $this->db_pull_timer = $sec;
        return $this;
    }

    /**
     * [setJobTimer description]
     *
     * @param int|integer $sec [description]
     */
    public function setJobTimer(int $sec=1)
    {
        $this->job_timer=$sec;
        return $this;
    }

    /**
     * [initialJobLoad description]
     *
     * @return [type] [description]
     */
    protected function initialJobLoad()
    {
        $that= $this;
        //this will call at once
        $this->loop->addTimer(
            0.5, function () use ($that) {
                $that->loadFromDatabase(true);
            }
        );
    }

    /**
     * [checkNewJobInDB description]
     *
     * @return [type] [description]
     */
    protected function checkNewJobInDB()
    {
        $that= $this;
        //timer for loading database
        $this->loop->addPeriodicTimer(
            $this->db_pull_timer, function () use ($that) {
                $that->loadFromDatabase(false);
            }
        );

    }

    /**
     * [setJobPriority description]
     *
     * @param array $jobs [description]
     */
    protected function setJobPriority(array $jobs)
    {
        usort(
            $jobs, function ($a,$b) {

                if($a['priority'] == $b['priority']) {
                    return 0;
                }
                return ($a['priority']<$b['priority'])?1:-1;
            }
        );
        return $jobs;

    }

    /**
     * [jobProcessRun description]
     *
     * @return [type] [description]
     */
    protected function jobProcessRun()
    {

        $jobs = &$this->queue;
        $that= $this;
        //timer for job process
        $this->loop->addPeriodicTimer(
            $this->job_timer, function () use (&$jobs,$that) {

                //print_r($jobs);
                if(!$jobs->isEmpty()) {
                    //going to start poing
                    $jobs->rewind();
                    //dequeue and get the job
                     $item = $jobs->dequeue();
                    try
                     {
                        if((int)$item['attempts'] < $that->maxAttempts) {
                              //convert payload to array
                             $payload = toArray(json_decode($item['payload']));
                             //create class
                             $clsName = 'App\Jobs\\'.$payload['handler'];
                             call_user_func_array([new $clsName(),'handle'], [$payload['data']]);
                             //update database
                            $that->queueStatusUpdate(
                                $item['id'], [
                                'status'=>'finish',
                                'attempts'=> (int)$item['attempts']+1,
                                'process_at'=> Carbon::now()->timestamp,
                                'result'=>'Successfully Executed'
                                ]
                            );
                                 unset($item);
                        }
                        else
                        {
                            $that->queueStatusUpdate(
                                $item['id'], [
                                'status'=>'finish'
                                ]
                            );
                        }

                    }
                    catch(\Throwable $e)
                    {
                        $that->queueStatusUpdate(
                            $item['id'], [
                            'status'=>'failed',
                            'attempts'=> (int)$item['attempts']+1,
                            'process_at'=> Carbon::now()->timestamp,
                            'result'=> $e
                            ]
                        );
                        unset($item);
                    }
                }

            }
        );
    }


    /**
     * [run description]
     *
     * @return [type] [description]
     */
    public function run() : void
    {
        //create loop
        $this->createFactoryLoop();

        //initialize the job queue
        $this->initialJobLoad();

        //check frequently for new job
        $this->checkNewJobInDB();

        //process job from queue
        $this->jobProcessRun();

        // $this->loop->addPeriodicTimer(5, function () {
        //     $memory = memory_get_usage() / 1024;
        //     $formatted = number_format($memory, 3).'K';
        //     echo "Current memory usage: {$formatted}\n";
        //     //print_r(get_declared_classes());
        // });
        //finally run the loop
        $this->loop->run();
    }

    /**
     * [loadFromDatabase description]
     *
     * @param  boolean $init [description]
     * @return [type]        [description]
     */
    public function loadFromDatabase($onStart=false)
    {
        //all data load except finished while starting the server
        if($onStart) {
            $sql ="SELECT * FROM jobs WHERE status != ?  ORDER BY schedule_at DESC";
            $rows = toArray(DB::rawQuery($sql, ['finish']));
        }
        else
        {
            $sql ="SELECT * FROM jobs WHERE status = ? OR status = ?  ORDER BY schedule_at DESC";
            $rows = toArray(DB::rawQuery($sql, ['pending','failed']));
        }

        if(is_array($rows) && !empty($rows)) {
            $rows = $this->setJobPriority($rows);
            foreach ($rows as $key)
            {
                $this->queue->enqueue($key);
                if($key['status'] ==='pending') {
                    $this->queueStatusUpdate(
                        $key['id'], [
                        'status'=> 'loaded'
                        ]
                    );
                }
            }
        }
    }

    /**
     * [queueStatusUpdate description]
     *
     * @param  int   $id     [description]
     * @param  array $params [description]
     * @return [type]         [description]
     */
    public function queueStatusUpdate(int $id,array $params)
    {
        if(is_null($params)) {
            return false;
        }

        return DB::table('jobs')->update($params)
            ->where('id', $id)->save();
    }
}
