<?php
declare(strict_types = 1);

namespace CoreAdmin\Controller;

use function Garethellis\CrontabScheduleGenerator\every;
use function Garethellis\CrontabScheduleGenerator\hourly;
use function Garethellis\CrontabScheduleGenerator\monthly;
use function Garethellis\CrontabScheduleGenerator\weekly;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use TiBeN\CrontabManager\CrontabAdapter;
use TiBeN\CrontabManager\CrontabJob;
use TiBeN\CrontabManager\CrontabRepository;

class ExampleController extends AbstractActionController
{
    private $container;
    private $config;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
    }

    /**
     * Tailwind Command
      npx tailwindcss -i ./src/css/CoreAdmin/Example/ckeditor/1_tailwind.css \
      -o ./public/css/CoreAdmin/Example/ckeditor/1_tailwind.css \
      --JIT --purge="./module/CoreAdmin/view/core-admin/example/ckeditor.phtml, \
      ./module/CoreAdmin/view/core-admin/example/ckeditor/*.phtml" --watch
    */
    public function ckeditorAction()
    {
        $me = $this;
        $request = $me->request;
        if ($request->isPost()) {
            $postdata = $me->params()->fromPost();
            zdebug($postdata);
            die();
        }
        return [];
    }

    public function crontabManagerAction()
    {
        $me = $this;

        //generate crontab config
        //example see https://github.com/garethellis36/crontab-schedule-generator
        //On Workday (Monday-Friday) at 01:00
        $workday_01 = weekly()
        ->on("Monday")->repeatingOn("Tuesday")
        ->repeatingOn("Wednesday")->repeatingOn("Thursday")
        ->repeatingOn("Friday")->at("01:00");

        //On Weekend (Saturday-Sunday) every hour at min 20
        $on_weekend = weekly()
        ->on("Saturday")->repeatingOn("Sunday")->__toString();
        $hourly_at20 = hourly("20")->at("20")->__toString();
        $tmp_weekend = explode(" ", $on_weekend);
        $tmp_hourly = explode(" ", $hourly_at20);
        $tmp_weekend[0] = $tmp_hourly[0];
        $tmp_weekend[1] = $tmp_hourly[1];
        $weekend_hourly_at20 = implode(" ", $tmp_weekend);

        //On third day of month every 2 hour at half hour from 07:00 until 17:00
        $thirdday_month = monthly()->on("3rd")->__toString();
        $every_half = every("2")->hours()->at("half past")->from("7")->until("17")->__toString();
        $tmp_month = explode(" ", $thirdday_month);
        $tmp_every = explode(" ", $every_half);
        $tmp_month[0] = $tmp_every[0];
        $tmp_month[1] = $tmp_every[1];
        $thirddaymonth_everyhalf_0717 = implode(" ", $tmp_month);

        //init crontab repo
        $crontabRepository = new CrontabRepository(new CrontabAdapter());

        $jobComment = "myjob-crontab";
        $crontabJob = $crontabRepository->findJobByRegex('/(#'.$jobComment.')/');
        $command = "php /home/admfrm/lamira-frames-dev/public/index.php myjob";
        $cronMsg = "Job Exist";
        // !d($crontabJob);die();
      if (count($crontabJob)<=0) { //check job exist
        //init crontab job
        $crontabJob = CrontabJob::createFromCrontabLine($workday_01." ".$command);
          $crontabJob->setComments($jobComment);

          //add crontab job to crontab repo
          $crontabRepository->addJob($crontabJob);
          $crontabRepository->persist();

          $cronMsg = "Job Created";
      } else {
          //remove job
          $crontabRepository->removeJob($crontabJob[0]);
          $crontabRepository->persist();
          $cronMsg = "Job Removed";
      }

        $viewModel = new JsonModel();
        $viewModel->setVariables([
        'workday_01' => $workday_01->__toString(),
        'weekend_hourly_at20' => $weekend_hourly_at20,
        'thirddaymonth_everyhalf_0717' => $thirddaymonth_everyhalf_0717,
        'cronMsg' => $cronMsg
      ]);
        return $viewModel;
    }

    public function sendSingleMailAction()
    {
        $is_sent = false;
        $err_msg = "";
        $dump_mail = "";
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV') . ".conf");
        $smtp_conf = $conf['smtp'];
        // !d($smtp_conf['frames']);die();
        try {
            // Setup SMTP transport
            $transport = new SmtpTransport();
            $options   = new SmtpOptions([
                // 'name' => $smtp_conf['frames']['name'],
                'host' => $smtp_conf['frames']['host'],
                'connection_class' => $smtp_conf['frames']['auth'],
                'port' => $smtp_conf['frames']['port'],
                'connection_config' => [
                    'username' => $smtp_conf['frames']['username'],
                    'password' => $smtp_conf['frames']['password'],
                    // 'ssl' => $smtp_conf['frames']['ssl'],
                    'port' => $smtp_conf['frames']['port']
                ],
            ]);
            $transport->setOptions($options);

            $message = new Message();
            $message->addFrom('info@tma.web.id', 'Info TMA');
            // $message->addFrom('ralph@example.org', 'Ralph Nader');
            // $message->addFrom('enrico@example.org', 'Enrico Volante');
            $message->addTo('rohimfikri@gmail.com');
            $message->addTo('sonjayaarizal@gmail.com');
            $message->addTo('mseptiyadi12@gmail.com');
            $message->setSubject('Sending an email from Laminas\Mail!');
            $message->setBody('This is the message body.');
            // $message->addCc('ralph@example.org');
            // $message->addBcc('enrico@example.org');
            // $message->addReplyTo('matthew@example.com', 'Matthew');
            $message->setSender('info@tma.web.id', 'Info TMA');
            // $message->setEncoding('UTF-8');
            $dump_mail = $message->toString();
            $send = $transport->send($message);
            // !d($dump_mail,$send);
            $is_sent = true;
        } catch (\Exception $e) {
            // !d($dump_mail,$send);
            $err_msg = $e->getMessage();
        }

        $viewModel = new JsonModel();
        $viewModel->setVariables([
        'msg' => $dump_mail,
        'send_status' => $is_sent,
        'error_msg' => $err_msg
      ]);
        return $viewModel;
    }

    public function odbcNetezzaAction()
    {
        $nzframes_mdl = $this->container->get(\CoreAdmin\Model\NzModel::class);
        $data = $nzframes_mdl->getFramesCollection();
        $viewModel = new JsonModel();
        $viewModel->setVariables($data);
        return $viewModel;
    }

    public function apexChartAction()
    {
        // die('aaa');
        $this->layout()->setTemplate('blank');
        return [];
    }
}
