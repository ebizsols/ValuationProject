<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Tasks\StoreTask;
use App\Notifications\NewClientTask;
use App\Notifications\NewTask;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskReminder;
use App\Notifications\TaskUpdated;
use App\Notifications\TaskUpdatedClient;
use App\Project;
use App\ProjectMember;
use App\Task;
use App\TaskboardColumn;
use App\TaskCategory;
use App\TaskFile;
use App\Traits\ProjectProgress;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ManageAllTasksController extends AdminBaseController
{
    use ProjectProgress;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.tasks';
        $this->pageIcon = 'ti-layout-list-thumb';
        $this->middleware(function ($request, $next) {
            if (!in_array('tasks', $this->user->modules)) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $this->projects = Project::all();
        $this->clients = User::allClients();
        $this->employees = User::allEmployees();
        $this->taskBoardStatus = TaskboardColumn::all();

        return view('admin.tasks.index', $this->data);
    }

    public function data(Request $request)
    {
        $startDate = Carbon::createFromFormat($this->global->date_format, $request->startDate)->toDateString();
        $endDate = Carbon::createFromFormat($this->global->date_format, $request->endDate)->toDateString();
        $projectId = $request->projectId;
        $hideCompleted = $request->hideCompleted;

        $taskBoardColumn = TaskboardColumn::where('slug', 'incomplete')->first();

        $tasks = Task::leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->with('user')
            ->leftJoin('users as client', 'client.id', '=', 'projects.client_id')
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->join('taskboard_columns', 'taskboard_columns.id', '=', 'tasks.board_column_id')
            ->leftJoin('users as creator_user', 'creator_user.id', '=', 'tasks.created_by')
            ->select('tasks.id', 'projects.project_name', 'tasks.heading', 'client.name as clientName', 'users.name', 'creator_user.name as created_by', 'creator_user.image as created_image', 'users.image', 'tasks.due_date', 'taskboard_columns.column_name', 'taskboard_columns.label_color', 'tasks.project_id')
            ->whereNull('projects.deleted_at');

        $tasks->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween(DB::raw('DATE(tasks.`due_date`)'), [$startDate, $endDate]);

            $q->orWhereBetween(DB::raw('DATE(tasks.`start_date`)'), [$startDate, $endDate]);
        });

        if ($projectId != 0 && $projectId !=  null && $projectId !=  'all') {
            $tasks->where('tasks.project_id', '=', $projectId);
        }

        if ($request->clientID != '' && $request->clientID !=  null && $request->clientID !=  'all') {
            $tasks->where('projects.client_id', '=', $request->clientID);
        }

        if ($request->assignedTo != '' && $request->assignedTo !=  null && $request->assignedTo !=  'all') {
            $tasks->where('tasks.user_id', '=', $request->assignedTo);
        }

        if ($request->assignedBY != '' && $request->assignedBY !=  null && $request->assignedBY !=  'all') {
            $tasks->where('creator_user.id', '=', $request->assignedBY);
        }

        if ($request->status != '' && $request->status !=  null && $request->status !=  'all') {
            $tasks->where('tasks.board_column_id', '=', $request->status);
        }

        if ($hideCompleted == '1') {
            $tasks->where('tasks.board_column_id', $taskBoardColumn->id);
        }

        $tasks->get();

        return DataTables::of($tasks)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $action = '<a href="' . route('admin.all-tasks.edit', $row->id) . '" class="btn btn-info btn-circle"
                      data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-pencil" aria-hidden="true"></i></a>';

                $recurringTaskCount = Task::where('recurring_task_id', $row->id)->count();
                $recurringTask = $recurringTaskCount > 0 ? 'yes' : 'no';

                $action .= '&nbsp;&nbsp;<a href="javascript:;" class="btn btn-danger btn-circle sa-params"
                      data-toggle="tooltip" data-task-id="' . $row->id . '" data-recurring="' . $recurringTask . '" data-original-title="Delete"><i class="fa fa-times" aria-hidden="true"></i></a>';

                return $action;
            })
            ->editColumn('due_date', function ($row) {
                if ($row->due_date->isPast()) {
                    return '<span class="text-danger">' . $row->due_date->format($this->global->date_format) . '</span>';
                }
                return '<span class="text-success">' . $row->due_date->format($this->global->date_format) . '</span>';
            })
            ->editColumn('name', function ($row) {
                $image_url = $row->image?asset_url('avatar/'.$row->image):asset('default-profile-2.png');
                return '<img src="' . $image_url . '" alt="user" class="img-circle" width="30" height="30"> '. ucwords($row->name);
            })
            ->editColumn('clientName', function ($row) {
                return ($row->clientName) ? ucwords($row->clientName) : '-';
            })
            ->editColumn('created_by', function ($row) {
                if (!is_null($row->created_by)) {
                    return ($row->created_image) ? '<img src="' . asset_url('avatar/' . $row->created_image) . '"
                                                            alt="user" class="img-circle" width="30" height="30"> ' . ucwords($row->created_by) : '<img src="' . asset('default-profile-2.png') . '"
                                                            alt="user" class="img-circle" width="30" height="30"> ' . ucwords($row->created_by);
                }
                return '-';
            })
            ->editColumn('heading', function ($row) {
                return '<a href="javascript:;" data-task-id="' . $row->id . '" class="show-task-detail">' . ucfirst($row->heading) . '</a>';
            })
            ->editColumn('column_name', function ($row) {
                return '<label class="label" style="background-color: ' . $row->label_color . '">' . $row->column_name . '</label>';
            })
            ->editColumn('project_name', function ($row) {
                if (is_null($row->project_id)) {
                    return "";
                }
                return '<a href="' . route('admin.projects.show', $row->project_id) . '">' . ucfirst($row->project_name) . '</a>';
            })
            ->rawColumns(['column_name', 'action', 'project_name', 'clientName', 'due_date', 'name', 'created_by', 'heading'])
            ->removeColumn('project_id')
            ->removeColumn('image')
            ->removeColumn('created_image')
            ->removeColumn('label_color')
            ->make(true);
    }

    public function edit($id)
    {
        $this->task = Task::findOrFail($id);
        $this->projects = Project::all();
        $this->employees = User::allEmployees();
        $this->categories = TaskCategory::all();
        $this->taskBoardColumns = TaskboardColumn::all();
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if($completedTaskColumn)
        {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)
                ->where('id', '!=', $id);

            if($this->task->project_id != '')
            {
                $this->allTasks = $this->allTasks ->where('project_id', $this->task->project_id);
            }

            $this->allTasks = $this->allTasks->get();
        }else {
            $this->allTasks = [];
        }

        return view('admin.tasks.edit', $this->data);
    }

    public function update(StoreTask $request, $id)
    {
        $task = Task::findOrFail($id);
        $oldStatus = TaskboardColumn::findOrFail($task->board_column_id);

        $task->heading = $request->heading;
        if ($request->description != '') {
            $task->description = $request->description;
        }
        $task->start_date = Carbon::createFromFormat($this->global->date_format, $request->start_date)->format('Y-m-d');
        $task->due_date = Carbon::createFromFormat($this->global->date_format, $request->due_date)->format('Y-m-d');
        $task->user_id = $request->user_id;
        $task->task_category_id = $request->category_id;
        $task->priority = $request->priority;
        $task->board_column_id = $request->status;
        $task->dependent_task_id = $request->has('dependent') && $request->dependent == 'yes' && $request->has('dependent_task_id') && $request->dependent_task_id != '' ? $request->dependent_task_id : null;

        $taskBoardColumn = TaskboardColumn::findOrFail($request->status);

        if ($taskBoardColumn->slug == 'completed') {
            $task->completed_on = Carbon::now()->format('Y-m-d H:i:s');
        } else {
            $task->completed_on = null;
        }

        $task->project_id = $request->project_id;
        $task->save();

        $this->calculateProjectProgress($request->project_id);

        return Reply::dataOnly(['taskID' => $task->id]);
        //        return Reply::redirect(route('admin.all-tasks.index'), __('messages.taskUpdatedSuccessfully'));
    }

    public function destroy(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        // If it is recurring and allowed by user to delete all its recurring tasks
        if ($request->has('recurring') && $request->recurring == 'yes') {
            Task::where('recurring_task_id', $id)->delete();
        }

        $taskFiles = TaskFile::where('task_id', $id)->get();

        foreach ($taskFiles as $file) {
            Files::deleteFile($file->hashname, 'task-files/' . $file->task_id);
            $file->delete();
        }

        Task::destroy($id);
        //calculate project progress if enabled
        $this->calculateProjectProgress($task->project_id);

        return Reply::success(__('messages.taskDeletedSuccessfully'));
    }

    public function create()
    {
        $this->projects = Project::all();
        $this->employees = User::allEmployees();
        $this->categories = TaskCategory::all();
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if($completedTaskColumn)
        {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)->get();
        }else {
            $this->allTasks = [];
        }
        return view('admin.tasks.create', $this->data);
    }

    public function membersList($projectId)
    {
        $this->members = ProjectMember::byProject($projectId);
        $list = view('admin.tasks.members-list', $this->data)->render();
        return Reply::dataOnly(['html' => $list]);
    }

    public function dependentTaskLists($projectId, $taskId = null)
    {
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if($completedTaskColumn)
        {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)
                ->where('project_id', $projectId);

            if($taskId != null)
            {
                $this->allTasks = $this->allTasks->where('id', '!=', $taskId);
            }

            $this->allTasks = $this->allTasks->get();
        }else {
            $this->allTasks = [];
        }

        $list = view('admin.tasks.dependent-task-list', $this->data)->render();
        return Reply::dataOnly(['html' => $list]);
    }

    public function store(StoreTask $request)
    {

        $ganttTaskArray = [];
        $gantTaskLinkArray = [];
        $taskBoardColumn = TaskboardColumn::where('slug', 'incomplete')->first();
        $task = new Task();
        $task->heading = $request->heading;
        if ($request->description != '') {
            $task->description = $request->description;
        }
        $task->start_date = Carbon::createFromFormat($this->global->date_format, $request->start_date)->format('Y-m-d');
        $task->due_date = Carbon::createFromFormat($this->global->date_format, $request->due_date)->format('Y-m-d');
        $task->user_id = $request->user_id;
        $task->project_id = $request->project_id;
        $task->task_category_id = $request->category_id;
        $task->priority = $request->priority;
        $task->board_column_id = $taskBoardColumn->id;
        $task->created_by = $this->user->id;
        $task->dependent_task_id = $request->has('dependent') && $request->dependent == 'yes' && $request->has('dependent_task_id') && $request->dependent_task_id != '' ? $request->dependent_task_id : null;

        if ($request->board_column_id) {
            $task->board_column_id = $request->board_column_id;
        }

        if ($taskBoardColumn->slug == 'completed') {
            $task->completed_on = Carbon::now()->format('Y-m-d H:i:s');
        } else {
            $task->completed_on = null;
        }

        $task->save();

        // For gantt chart
        if ($request->page_name && $request->page_name == 'ganttChart') {
            $parentGanttId = $request->parent_gantt_id;

            $taskDuration = $task->due_date->diffInDays($task->start_date);
            $taskDuration = $taskDuration + 1;

            $ganttTaskArray[] = [
                'id' => $task->id,
                'text' => $task->heading,
                'start_date' => $task->start_date->format('Y-m-d'),
                'duration' => $taskDuration,
                'parent' => $parentGanttId,
                'users' => [
                    ucwords($task->user->name)
                ],
                'taskid' => $task->id
            ];

            $gantTaskLinkArray[] = [
                'id' => 'link_' . $task->id,
                'source' => $parentGanttId,
                'target' => $task->id,
                'type' => 1
            ];
        }

        // Add repeated task
        if ($request->has('repeat') && $request->repeat == 'yes') {
            $repeatCount = $request->repeat_count;
            $repeatType = $request->repeat_type;
            $repeatCycles = $request->repeat_cycles;
            $startDate = Carbon::createFromFormat($this->global->date_format, $request->start_date)->format('Y-m-d');;
            $dueDate = Carbon::createFromFormat($this->global->date_format, $request->due_date)->format('Y-m-d');;


            for ($i = 1; $i < $repeatCycles; $i++) {
                $repeatStartDate = Carbon::createFromFormat('Y-m-d', $startDate);
                $repeatDueDate = Carbon::createFromFormat('Y-m-d', $dueDate);

                if ($repeatType == 'day') {
                    $repeatStartDate = $repeatStartDate->addDays($repeatCount);
                    $repeatDueDate = $repeatDueDate->addDays($repeatCount);
                } else if ($repeatType == 'week') {
                    $repeatStartDate = $repeatStartDate->addWeeks($repeatCount);
                    $repeatDueDate = $repeatDueDate->addWeeks($repeatCount);
                } else if ($repeatType == 'month') {
                    $repeatStartDate = $repeatStartDate->addMonths($repeatCount);
                    $repeatDueDate = $repeatDueDate->addMonths($repeatCount);
                } else if ($repeatType == 'year') {
                    $repeatStartDate = $repeatStartDate->addYears($repeatCount);
                    $repeatDueDate = $repeatDueDate->addYears($repeatCount);
                }

                $newTask = new Task();
                $newTask->heading = $request->heading;
                if ($request->description != '') {
                    $newTask->description = $request->description;
                }
                $newTask->start_date = $repeatStartDate->format('Y-m-d');
                $newTask->due_date = $repeatDueDate->format('Y-m-d');
                $newTask->user_id = $request->user_id;
                $newTask->project_id = $request->project_id;
                $newTask->task_category_id = $request->category_id;
                $newTask->priority = $request->priority;
                $newTask->board_column_id = $taskBoardColumn->id;
                $newTask->created_by = $this->user->id;
                $newTask->recurring_task_id = $task->id;

                if ($request->board_column_id) {
                    $newTask->board_column_id = $request->board_column_id;
                }

                if ($taskBoardColumn->slug == 'completed') {
                    $newTask->completed_on = Carbon::now()->format('Y-m-d H:i:s');
                } else {
                    $newTask->completed_on = null;
                }

                $newTask->save();

                // For gantt chart
                if ($request->page_name && $request->page_name == 'ganttChart') {
                    $parentGanttId = $request->parent_gantt_id;
                    $taskDuration = $newTask->due_date->diffInDays($newTask->start_date);
                    $taskDuration = $taskDuration + 1;

                    $ganttTaskArray[] = [
                        'id' => $newTask->id,
                        'text' => $newTask->heading,
                        'start_date' => $newTask->start_date->format('Y-m-d'),
                        'duration' => $taskDuration,
                        'parent' => $parentGanttId,
                        'users' => [
                            ucwords($newTask->user->name)
                        ],
                        'taskid' => $newTask->id
                    ];

                    $gantTaskLinkArray[] = [
                        'id' => 'link_' . $newTask->id,
                        'source' => $parentGanttId,
                        'target' => $newTask->id,
                        'type' => 1
                    ];
                }

                $startDate = $newTask->start_date->format('Y-m-d');
                $dueDate = $newTask->due_date->format('Y-m-d');
            }
        }

        //calculate project progress if enabled
        $this->calculateProjectProgress($request->project_id);

        if (!is_null($request->project_id)) {
            $this->logProjectActivity($request->project_id, __('messages.newTaskAddedToTheProject'));
        }

        //log search
        $this->logSearchEntry($task->id, 'Task ' . $task->heading, 'admin.all-tasks.edit', 'task');

        if ($request->page_name && $request->page_name == 'ganttChart') {

            return Reply::successWithData(
                'messages.taskCreatedSuccessfully',
                [
                    'tasks' => $ganttTaskArray,
                    'links' => $gantTaskLinkArray
                ]
            );
        }

        if ($request->board_column_id) {
            return Reply::redirect(route('admin.taskboard.index'), __('messages.taskCreatedSuccessfully'));
        }

        return Reply::dataOnly(['taskID' => $task->id]);
        //        return Reply::redirect(route('admin.all-tasks.index'), __('messages.taskCreatedSuccessfully'));
    }

    public function ajaxCreate($columnId)
    {
        $this->projects = Project::all();
        $this->columnId = $columnId;
        $this->employees = User::allEmployees();
        $completedTaskColumn = TaskboardColumn::where('slug', '!=', 'completed')->first();
        if($completedTaskColumn)
        {
            $this->allTasks = Task::where('board_column_id', $completedTaskColumn->id)->get();
        }else {
            $this->allTasks = [];
        }
        return view('admin.tasks.ajax_create', $this->data);
    }

    public function remindForTask($taskID)
    {
        $task = Task::with('user')->findOrFail($taskID);

        // Send  reminder notification to user
        $notifyUser = $task->user;
        $notifyUser->notify(new TaskReminder($task));

        return Reply::success('messages.reminderMailSuccess');
    }

    public function show($id)
    {
        $this->task = Task::with('board_column')->findOrFail($id);
        $view = view('admin.tasks.show', $this->data)->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function showFiles($id)
    {
        $this->taskFiles = TaskFile::where('task_id', $id)->get();
        return view('admin.tasks.ajax-file-list', $this->data);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $projectId
     * @param $hideCompleted
     */
    public function export($startDate, $endDate, $projectId, $hideCompleted)
    {

        $tasks = Task::leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->join('taskboard_columns', 'taskboard_columns.id', '=', 'tasks.board_column_id')
            ->select('tasks.id', 'projects.project_name', 'tasks.heading', 'users.name', 'users.image', 'taskboard_columns.column_name', 'tasks.due_date', 'tasks.start_date');

        $tasks->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween(DB::raw('DATE(tasks.`due_date`)'), [$startDate, $endDate]);

            $q->orWhereBetween(DB::raw('DATE(tasks.`start_date`)'), [$startDate, $endDate]);
        });

        if ($projectId != 0) {
            $tasks->where('tasks.project_id', '=', $projectId);
        }

        if ($hideCompleted == '1') {
            $tasks->where('tasks.status', '=', 'incomplete');
        }

        $attributes =  ['image', 'due_date'];

        $tasks = $tasks->get()->makeHidden($attributes);

        // Initialize the array which will be passed into the Excel
        // generator.
        $exportArray = [];

        // Define the Excel spreadsheet headers
        $exportArray[] = ['ID', 'Project', 'Title', 'Assigned To', 'Status', 'Due Date'];

        // Convert each member of the returned collection into an array,
        // and append it to the payments array.
        foreach ($tasks as $row) {
            $exportArray[] = $row->toArray();
        }

        // Generate and return the spreadsheet
        Excel::create('task', function ($excel) use ($exportArray) {

            // Set the spreadsheet title, creator, and description
            $excel->setTitle('Task');
            $excel->setCreator('Worksuite')->setCompany($this->companyName);
            $excel->setDescription('task file');

            // Build the spreadsheet, passing in the payments array
            $excel->sheet('sheet1', function ($sheet) use ($exportArray) {
                $sheet->fromArray($exportArray, null, 'A1', false, false);

                $sheet->row(1, function ($row) {

                    // call row manipulation methods
                    $row->setFont(array(
                        'bold'       =>  true
                    ));
                });
            });
        })->download('xlsx');
    }
}
