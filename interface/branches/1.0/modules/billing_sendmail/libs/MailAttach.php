<?

/*
* @author 		Wilson Gomes <gomesneto@yahoo.com> =)
*
* @since 		01/03/2002
*
* This is a sample code to send your e-mail with attachments...
* $filename     = $HTTP_POST_FILES['file']['name'];
* $tempFilename     = $HTTP_POST_FILES['file']['tmp_name'];
* $content_type = $HTTP_POST_FILES['file']['type'];
* if(isset($filename)){
*	# read a JPEG picture from the disk
*	
*	@$fd = fopen($tempFilename, "r");
*	
*	@$data = fread($fd, filesize($tempFilename));
*	
*	@fclose($fd);
* }
*
* # create object instance
* $mail = new MailAttach;
*
* # set all data slots
* $mail->from    = $Email;
* $mail->to      = $to;
* $mail->subject = $subject;
* $mail->body    = "Name:".$Nome."\nE-mail: ".$Email."\nTelefone: ".$Fone."\nFAX: ".$Fax."\n
* Nome do drink: ".$NomeDrink."\nTipo do Drink: ".$TipoDrink."
* Ingredientes:\n".$Ingredientes."
* "."Instrucoes:\n".$Instrucoes;
*
* if($filename <> "")
* {
*	# append the attachment
*	$mail->add_attachment($data, $filename, $content_type);
* }
*
* # send e-mail
* $enviado = $mail->send();
*
*/


class MailAttach
{

   var $parts;

   var $to;

   var $from;

   var $headers;

   var $subject;

   var $body;



   /*

    *     void MailAttach()

    *     class constructor

    */



   function MailAttach() {

      $this->parts = array();

      $this->to =  "";

      $this->from =  "";

      $this->subject =  "";

      $this->body =  "";

      $this->headers =  "";

   }



   /*

    *     void add_attachment(string message, [string name], [string ctype])

    *     Add an attachment to the mail object

    */



   function add_attachment($message, $name =  "", $ctype = "application/octet-stream",$encode='') {

      $this->parts[] = array (

            "ctype" => $ctype,

            "message" => $message,

            "encode" => $encode,

            "name" => $name

                           );

   }



   /*

    *      void build_message(array part=

    *      Build message parts of an multipart mail

    */



   function build_message($part) {

      $message = $part[ "message"];

      $message = chunk_split(base64_encode($message));

      $encoding =  "base64";

      return  "Content-Type: ".$part[ "ctype"].

         ($part[ "name"]? "; name = \"".$part[ "name"].

         "\"" :  "").



         "\nContent-Transfer-Encoding: $encoding\n\n$message\n";

   }



   /*

    *      void build_multipart()

    *      Build a multipart mail

    */



   function build_multipart() {

      $boundary =  "b".md5(uniqid(time()));

      $multipart =

         "Content-Type: multipart/mixed; boundary = $boundary\n\nThis is a MIME encoded message.\n\n--$boundary";



         for($i = sizeof($this->parts)-1; $i >= 0; $i--)

      {

         $multipart .=  "\n".$this->build_message($this->parts[$i]).

            "--$boundary";

      }

      return $multipart.=  "--\n";

   }



   /*

    *      string get_mail()

    *      returns the constructed mail

    */



   function get_mail($complete = true) {

      $mime =  "";

      if (!empty($this->from))

         $mime .=  "From: ".$this->from. "\n";

      if (!empty($this->headers))

         $mime .= $this->headers. "\n";



      if ($complete) {

         if (!empty($this->to)) {

            $mime .= "To: $this->to\n";

         }

         if (!empty($this->subject)) {

            $mime .= "Subject: $this->subject\n";

         }

      }



      if (!empty($this->body))

         $this->add_attachment($this->body,  "",  "text/plain");

      $mime .=  "MIME-Version: 1.0\n".$this->build_multipart();



      return $mime;

   }



   /*

    *      void send()

    *      Send the mail (last class-function to be called)

    */



   function send() {

      $mime = $this->get_mail(false);

      if (mail($this->to, $this->subject,  "", $mime)) {

		return true;

	}else{

		return false;

	}



   }

};

?>

