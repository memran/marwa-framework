# AI Coding Guide

## Purpose

Use the built-in AI scaffolding when you want a lightweight application-specific helper that can hold prompts, input shaping, or response formatting logic without coupling the framework to any AI provider.

## Configuration

Create `config/ai.php` in your application:

```php
return [
    'default' => 'ollama',
    
    // Example providers (uncomment to use):
    /*
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4o',
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => 'claude-3-opus',
        ],
        'google' => [
            'api_key' => env('GOOGLE_API_KEY'),
            'model' => 'gemini-pro',
        ],
    ],
    */
];
```

## Helper Functions

```php
// Get AI manager
ai()

// Generate text completion
ai_complete('Write a welcome email');

// Start a conversation
ai_conversation([
    ['role' => 'system', 'content' => 'You are a helpful assistant'],
    ['role' => 'user', 'content' => 'Hello'],
])

// Generate embeddings
ai_embed(['Hello world', 'Another text'])
```

## Console Commands

```bash
# Generate text completion
php marwa ai:complete "What is PHP?"

# List available providers
php marwa ai:providers

# Interactive chat
php marwa ai:chat
```

## Usage Examples

### In Mailable (Email Writing)

```php
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Contracts\MailerInterface;

class WelcomeEmail extends Mailable
{
    public function build(MailerInterface $mailer): MailerInterface
    {
        $content = ai_complete('Write a welcome email for new user');
        
        return $mailer
            ->subject('Welcome!')
            ->to('user@example.com')
            ->html($content);
    }
}
```

### In Console Command

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateReportCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = ai_complete('Generate a summary report');
        $output->writeln($result);
        
        return Command::SUCCESS;
    }
}
```

### Custom AI Tools

Implement `AIToolInterface` for custom tools:

```php
use Marwa\Framework\Contracts\AIToolInterface;

class WeatherTool implements AIToolInterface
{
    public function name(): string
    {
        return 'weather';
    }

    public function description(): string
    {
        return 'Get current weather for a location';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'location' => ['type' => 'string'],
            ],
            'required' => ['location'],
        ];
    }

    public function execute(array $args): string
    {
        // Your implementation
        return "Weather in {$args['location']}: 22°C";
    }
}

// Register tool
ai()->tool(new WeatherTool());
```

## Generate Stubs

```bash
php marwa make:ai-helper SupportAgent --with-command
```

This generates:

- `app/AI/SupportAgent.php`
- `app/Console/Commands/SupportAgentCommand.php`

## Recommended Usage

- Keep provider SDK calls outside the framework core
- Store prompt-building logic in dedicated helper classes
- Treat generated stubs as starting points, not production-ready AI orchestration
- Add tests for prompt shaping, payload normalization, and failure handling in the consuming application
