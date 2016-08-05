<?php

namespace Discord\Bot;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\Game;
use Evenement\EventEmitter;
use Monolog\Logger;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Provides an easy interface to build your own bot.
 */
class CommandBot extends EventEmitter
{
    /**
     * The DiscordPHP instance.
     *
     * @var Discord The DiscordPHP instance.
     */
    protected $discord;

    /**
     * The ReactPHP event loop.
     *
     * @var LoopInterface The event loop.
     */
    protected $loop;

    /**
     * The Monolog logger.
     *
     * @var Logger Monolog logger.
     */
    protected $log;

    /**
     * The config array.
     *
     * @var array The config array.
     */
    protected $config = [];

    /**
     * The commands array.
     *
     * @var array The commands array.
     */
    protected $commands = [];

    /**
     * The help array.
     *
     * @var array The help array.
     */
    protected $help = [];

    /**
     * Constructs a bot instance.
     *
     * @param array $config A config array.
     * @param LoopInterface|null $loop The ReactPHP event loop.
     * @param Logger $log The Monolog logger.
     *
     * @return void
     */
    public function __construct(array $config = [], $loop = null, $log = null)
    {
        $this->setupConfig($config);
        $this->log = $log ?: new Logger($this->config['name']);

        $this->log->addNotice("Booting {$this->config['name']}...");
        $this->loop = is_null($loop) ? Factory::create() : $loop;
        $this->discord = new Discord([
            'token' => $config['bot-token'],
            'loop' => $this->loop,
            'logging' => true, // Note: Set this to false if you want to disable logging.
        ]);

        $this->discord->on('ready', function ($discord) {
            $this->log->addInfo('Discord is ready.');
            $this->emit('ready', [$this->config, $discord, $this]);

            $this->discord->updatePresence($this->discord->factory(Game::class, [
                'name' => 'Type !help for help :o',
            ]));

            $this->discord->on('message', function (Message $message) {
                $params = explode(' ', strtolower($message->content));
                $command = @$params[0];
                array_shift($params); // Remove the prefix

                foreach ($this->commands as $trigger => $listener) {
                    $expected = $this->config['prefix'] . $trigger;

                    if ($command == $expected) {
                        $this->log->addInfo("User {$message->author->username}#{$message->author->discriminator} ({$message->author}) ran command '{$expected}'",
                            $params);
                        $this->emit('command-triggered', [$expected, $message->author]);
                        call_user_func_array($listener, [$params, $message, $this, $this->discord]);
                    }
                }
            });
        });

        $this->discord->on('reconnecting', function () {
            $this->log->addWarning('Discord WebSocket is reconnecting...');
        });
        $this->discord->on('reconnected', function () {
            $this->log->addWarning('Discord WebSocket has reconnected.');
        });

        $this->discord->on('close', function ($op, $reason) {
            $this->log->addWarning('Discord WebSocket closed.', ['op' => $op, 'reason' => $reason]);
        });

        $this->discord->on('error', function ($e) {
            $this->log->addError('Discord WebSocket encountered an error.', [$e]);
        });
    }

    /**
     * Adds a command listener.
     *
     * @param string $command The command to invoke the callback on.
     * @param callable $listener The callback to invoke.
     *
     * @return void
     */
    public function addCommand($command, callable $listener)
    {
        $this->commands[$command] = $listener;
    }

    /**
     * Updates the config.
     *
     * @param array $config The values to update.
     *
     * @return void
     */
    public function updateConfig(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Sets up the config.
     *
     * @param array $config The supplied config.
     *
     * @return void
     */
    protected function setupConfig(array $config = [])
    {
        $defaults = [
            'prefix' => '!',
            'name' => 'CommandBot',
        ];

        $this->config = array_merge($defaults, $config);
    }

    /**
     * Starts the event loop.
     *
     * @return void
     */
    public function start()
    {
        $this->loop->run();
    }

    /**
     * Returns the Monolog logger.
     *
     * @return Logger The Monolog logger.
     */
    public function getLogger()
    {
        return $this->log;
    }
}