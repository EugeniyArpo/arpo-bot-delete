<?php
namespace Arpo\BotDelete\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;

class BotDelete extends Command
{
    /** @var State $state */
    private $state;

    /** @var ResourceConnection $resource */
    protected $resource;

    public function __construct
    (
        State $state,
        ResourceConnection $resource,
        string $name = null
    ) {
        $this->state = $state;
        $this->resource = $resource;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('bot:delete')->setDescription('Delete all user bots from customer and subscriber tables');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Customer bots search begin.");

        $connection = $this->resource->getConnection();
        $this->state->setAreaCode('frontend');
        $delete = "DELETE ";
        // Format to find customers with double upper case letter in name
        $formatFindDoubleUpperCaseLetter = 'FROM %1$s WHERE %2$s IN (
            SELECT implicitTemp.entity_id from (
                SELECT `entity_id` FROM `customer_entity` WHERE `firstname` REGEXP BINARY \'[A-Z][A-Z]\'
                UNION
                SELECT `entity_id` FROM `customer_entity` WHERE `lastname` REGEXP BINARY \'[A-Z][A-Z]\'
            ) implicitTemp
        )';

        // Clean newsletter_subscriber table
        $newsletterSubscriberBot = "FROM newsletter_subscriber 
            WHERE customer_id IN (
                SELECT entity_id FROM customer_entity
                WHERE lastname LIKE '%www%' OR lastname LIKE '%@%' OR lastname LIKE '%http%'
                    OR firstname LIKE '%www%' OR firstname LIKE '%@%' OR firstname LIKE '%http%'
            )
            OR subscriber_email LIKE '%@qq%' OR subscriber_email LIKE '%@99%' OR subscriber_email LIKE '%@126%'
            OR subscriber_email LIKE '%@139%' OR subscriber_email LIKE '%@162%' OR subscriber_email LIKE '%@163%'
            OR subscriber_email LIKE '%@765%' OR subscriber_email LIKE '%@168%' OR subscriber_email LIKE '%@189%'";

        $deleteNewsletterSubscriberBot = $delete . $newsletterSubscriberBot;
        $qtyNewsletterSubscriberBot = $connection->query($deleteNewsletterSubscriberBot)->rowCount();
        // Find subscribers customers with double upper case letter in name
        $deleteNewsletterSubscriberBotDoubleUpperCaseLetter = $delete . sprintf($formatFindDoubleUpperCaseLetter, 'newsletter_subscriber', 'customer_id');
        $qtyNewsletterSubscriberBot += $connection->query($deleteNewsletterSubscriberBotDoubleUpperCaseLetter)->rowCount();
        $output->writeln("Done! " . "$qtyNewsletterSubscriberBot" . " subscriber bots deleted!");

        // Clean customer_entity table
        $customerBot = "FROM customer_entity
            WHERE lastname LIKE '%www%' OR lastname LIKE '%@%' OR lastname LIKE '%http%'
            OR firstname LIKE '%www%' OR firstname LIKE '%@%' OR firstname LIKE '%http%'
            OR email LIKE '%@qq%' OR email LIKE '%@99%' OR email LIKE '%@126%'
            OR email LIKE '%@139%' OR email LIKE '%@162%' OR email LIKE '%@163%'
            OR email LIKE '%@765%' OR email LIKE '%@168%' OR email LIKE '%@189%'";

        $deleteCustomerBot = $delete . $customerBot;
        $qtyCustomerBot = $connection->query($deleteCustomerBot)->rowCount();
        // Find customers with double upper case letter in name
        $deleteCustomerBotDoubleUpperCaseLetter = $delete . sprintf($formatFindDoubleUpperCaseLetter, 'customer_entity', 'entity_id');
        $qtyCustomerBot += $connection->query($deleteCustomerBotDoubleUpperCaseLetter)->rowCount();
        $output->writeln("Done! " . "$qtyCustomerBot" . " customer bots deleted!");

        $output->writeln("Customer bots search end.");
    }
}