<?php
require __DIR__ . "/vendor/autoload.php";
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class WebServer implements MessageComponentInterface {
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // var_dump($conn);
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        // var_dump($data);
        if ($data['action'] === 'login') {
            $userId = $data['user_id'];
            
            foreach ($this->clients as $client) {
                if ($client !== $from && $client->user_id === $userId) {
                    $client->send(json_encode(['action' => 'logout']));
                }
            }

            $from->user_id = $userId;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // var_dump($conn);
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebServer()
        )
    ),
    8080 // Puerto que prefieras (8080 es un ejemplo)
);
echo "Servidor WebSocket ejecutándose en puerto 8080\n";
$server->run();

?>