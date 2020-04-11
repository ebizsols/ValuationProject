<?php

namespace App\Observers;

use App\Notifications\NewClientTask;
use App\Notifications\NewTask;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskUpdated;
use App\Notifications\TaskUpdatedClient;
use App\Task;
use App\TaskboardColumn;
use App\UniversalSearch;
use App\User;
use Illuminate\Support\Facades\Notification;

class TaskObserver
{

    public function saving(Task $task)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        if (company()) {
            $task->company_id = company()->id;
        }
    }

    public function creating(Task $task)
    {
        $taskBoard = TaskboardColumn::orderBy('id', 'asc')->first();
        $task->board_column_id = $taskBoard->id;

    }

    public function updating (Task $task)
    {
        if ($task->isDirty('status')) {
            $status = $task->status;

            if ($status == 'completed') {
                $task->board_column_id = TaskboardColumn::where('priority', 2)->first()->id;
                $task->column_priority = 1;
            } elseif ($status == 'incomplete') {
                $task->board_column_id = TaskboardColumn::where('priority', 1)->first()->id;
                $task->column_priority = 1;
            }
        }

        if ($task->isDirty('board_column_id') && $task->column_priority != 2) {
            $task->status = 'incomplete';
        }
    }

    public function created(Task $task)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if (request()->has('project_id') && request()->project_id != "all" && request()->project_id != '') {
                if ($task->project->client_id != null && $task->project->allow_client_notification == 'enable' && $task->project->client->status != 'deactive') {
                    $task->project->client->notify(new NewClientTask($task));
                }
            }

            //Send notification to user
            $userIds = request('user_id');
            if(is_array($userIds)){
                $taskUsers = User::withoutGlobalScope('active')->whereIn('id', $userIds)->get();
            }
            else{
                $taskUsers = User::withoutGlobalScope('active')->where('id', $userIds)->first();
            }
            if($taskUsers){
                Notification::send($taskUsers, new NewTask($task));
            }
        }
    }

    public function updated(Task $task)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $status = $task->status;

            if ($task->isDirty('status')) {

                $taskBoardColumn = TaskboardColumn::findOrFail($task->board_column_id);

                if ($taskBoardColumn->slug == 'completed') {
                    // send task complete notification
                    $task->user->notify(new TaskCompleted($task));

                    $admins = User::allAdmins($task->user_id);
                    Notification::send($admins, new TaskCompleted($task));

                    if (request()->project_id != "all") {
                        if ($task->project->client_id != null && $task->project->allow_client_notification == 'enable') {
                            $task->project->client->notify(new TaskCompleted($task));
                        }
                    }
                }
            }

            if (request('user_id')) {
                //Send notification to user
                $userIds = request('user_id');
                $taskUsers = User::withoutGlobalScope('active');

                if(is_array($userIds)){
                    $taskUsers = $taskUsers->whereIn('id', $userIds);
                }
                else{
                    $taskUsers = $taskUsers->get();
                }

                Notification::send($taskUsers, new TaskUpdated($task));
            }

            if (request()->project_id != "all") {
                if ($task->project->client_id != null && $task->project->allow_client_notification == 'enable') {
                    $task->project->client->notify(new TaskUpdatedClient($task));
                }
            }
        }
    }

    public function deleting(Task $task){
        $universalSearches = UniversalSearch::where('searchable_id', $task->id)->where('module_type', 'task')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
