<?php

namespace Alma\Woocommerce\Tests\Services;

use Alma\API\Endpoints\Configuration;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Services\IntegrationConfigurationUrlService;
use PHPUnit\Framework\TestCase;
use Alma\API\Client;
use function PHPUnit\Framework\assertNull;

class IntegrationConfigurationUrlServiceTest extends TestCase
{
    public function test_send()
    {
        $alma_settings = $this->createMock(AlmaSettings::class);
        $tool_helper = $this->createMock(ToolsHelper::class);
        $alma_logger = $this->createMock(AlmaLogger::class);

        $alma_settings->alma_client = $this->createMock(Client::class);
        $alma_settings->alma_client->configuration = $this->createMock(Configuration::class);

        $tool_helper->expects($this->once())
            ->method('url_for_webhook')
            ->with(ConstantsHelper::COLLECT_URL)
            ->willReturn('http://example.com/woocommerce_api_alma_collect_data_url');

        $alma_settings->alma_client->configuration->expects($this->once())
            ->method('sendIntegrationsConfigurationsUrl')
            ->with('http://example.com/woocommerce_api_alma_collect_data_url');

        $service = new IntegrationConfigurationUrlService($alma_settings, $tool_helper, $alma_logger);
        assertNull($service->send());
    }
}
