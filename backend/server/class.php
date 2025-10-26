<?php

class User
{
    private $pdo;
    private $mailer;

    public function __construct()
    {
        $host = "localhost";
        $db = "tourist_agency";
        $user = "root";
        $pass = "";
        $charset = "utf8mb4";

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            
            // Initialize mailer
            $this->initializeMailer();
        } catch(PDOException $e) {
            die("DB Connection Failed: ".$e->getMessage());
        }
    }

    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'moynulislamshimanto11@gmail.com';
        $this->mailer->Password = 'fkismgljfuellmim';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom('moynulislamshimanto11@gmail.com', 'Tourist Travel Agency');
        $this->mailer->isHTML(true);
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function login($username, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if($user && password_verify($password, $user['password'])) {
            return [
                'success' => true,
                'data' => [
                    'id' => $user['id'],
                    'employee_id' => $user['employee_id'],
                    'role' => $user['role'],
                ],
            ];
        }

        return ['success' => false, 'message' => 'Invalid login credentials'];
    }

    public function sendMail($email, $message, $subject, $attachment = [])
	{
		require_once __DIR__ . '/mailer/PHPMailer.php';
		require_once __DIR__ . '/mailer/SMTP.php';


		$mail = new PHPMailer\PHPMailer\PHPMailer();
		//$mail->SMTPDebug = 2;

		$mail->isSMTP();
		$mail->Host = 'smtp.gmail.com';
		$mail->SMTPAuth = true;
		$mail->Username = 'araman666@gmail.com';
		$mail->Password = 'upbbwchcqvwkzfzf';
		$mail->SMTPSecure = 'tls';
		$mail->Port = 587;

		$mail->setFrom('araman666@gmail.com','Cogent HR');
		$mail->addAddress($email);
		//Attachment
		foreach ($attachment as $filePath)
		{
			if(file_exists($filePath))
			{
				$mail->addAttachment($filePath);
			}
		}
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body = $message;

		if(!$mail->send())
		{
			$_SESSION['mailError'] = $mail->ErrorInfo;
			return false;
		}

		else
		{
			return true;
		}

	}
}