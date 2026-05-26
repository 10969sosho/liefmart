<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Shared\Helpers\SecurePathHelper;

class ValidateSecurePaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:validate-paths';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validates that all paths are secure and within tolerance (max 1 level up from Laravel base)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔒 Validating path security...');
        $this->line('');

        $securityStatus = SecurePathHelper::getSecurityStatus();
        
        $this->line("📁 Base Path: {$securityStatus['base_path']}");
        $this->line("🛡️  Max Parent Levels: {$securityStatus['max_parent_levels']}");
        $this->line('');

        // Display validation results
        foreach ($securityStatus['validation'] as $pathType => $result) {
            $status = $result['secure'] ? '✅' : '❌';
            $this->line("{$status} {$pathType}: {$result['message']}");
            
            if ($result['path']) {
                $this->line("   Path: {$result['path']}");
            }
        }
        
        $this->line('');

        // Display overall status
        if ($securityStatus['overall_secure']) {
            $this->info('🎉 All paths are secure and within tolerance!');
            $this->line('');
            $this->line('✅ System is safe from cross-domain access');
            $this->line('✅ Maximum 1 level up from Laravel base is allowed');
            $this->line('✅ No security risks detected');
        } else {
            $this->error('⚠️  Security validation failed!');
            $this->line('');
            $this->line('❌ Some paths exceed security tolerance');
            $this->line('❌ Potential security risk detected');
            $this->line('');
            $this->line('🔧 Recommendations:');
            foreach ($securityStatus['recommendations'] as $recommendation) {
                $this->line("   • {$recommendation}");
            }
        }
        
        $this->line('');
        $this->line('=== SECURITY VALIDATION COMPLETED ===');
        
        return $securityStatus['overall_secure'] ? 0 : 1;
    }
}
