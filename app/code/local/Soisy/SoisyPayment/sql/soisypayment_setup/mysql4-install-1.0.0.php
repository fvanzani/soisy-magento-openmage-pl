<?php
$installer = $this;
$installer->startSetup();
$installer->addAttribute("order", "soisy_token", ['type'  => 'varchar']);
$installer->endSetup();
