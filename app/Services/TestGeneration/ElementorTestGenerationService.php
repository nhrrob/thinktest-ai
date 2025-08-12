<?php

namespace App\Services\TestGeneration;

use Illuminate\Support\Facades\Log;

class ElementorTestGenerationService
{
    /**
     * Detect if the code contains Elementor widget patterns.
     */
    public function isElementorWidget(string $code): bool
    {
        $elementorPatterns = [
            'Widget_Base',
            'Controls_Manager',
            'Group_Control',
            'Elementor\\Widget_Base',
            'Elementor\\Controls_Manager',
            'get_name()',
            'get_title()',
            'get_icon()',
            'get_categories()',
            'register_controls()',
            'render()',
        ];

        foreach ($elementorPatterns as $pattern) {
            if (strpos($code, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze Elementor widget code and extract control information.
     */
    public function analyzeElementorWidget(string $code): array
    {
        $analysis = [
            'is_elementor_widget' => $this->isElementorWidget($code),
            'widget_name' => $this->extractWidgetName($code),
            'widget_title' => $this->extractWidgetTitle($code),
            'widget_icon' => $this->extractWidgetIcon($code),
            'widget_categories' => $this->extractWidgetCategories($code),
            'controls' => $this->extractControls($code),
            'control_sections' => $this->extractControlSections($code),
            'render_method' => $this->hasRenderMethod($code),
            'style_dependencies' => $this->extractStyleDependencies($code),
            'script_dependencies' => $this->extractScriptDependencies($code),
        ];

        Log::info('Elementor widget analysis completed', [
            'widget_name' => $analysis['widget_name'],
            'controls_count' => count($analysis['controls']),
            'sections_count' => count($analysis['control_sections']),
        ]);

        return $analysis;
    }

    /**
     * Generate enhanced Elementor widget tests.
     */
    public function generateElementorWidgetTests(array $analysis, string $framework = 'phpunit'): string
    {
        if (!$analysis['is_elementor_widget']) {
            return '';
        }

        $tests = [];
        
        // Basic widget tests
        $tests[] = $this->generateBasicWidgetTests($analysis, $framework);
        
        // Control tests
        if (!empty($analysis['controls'])) {
            $tests[] = $this->generateControlTests($analysis['controls'], $framework);
        }
        
        // Control section tests
        if (!empty($analysis['control_sections'])) {
            $tests[] = $this->generateControlSectionTests($analysis['control_sections'], $framework);
        }
        
        // Render tests
        if ($analysis['render_method']) {
            $tests[] = $this->generateRenderTests($analysis, $framework);
        }
        
        // Dependency tests
        $tests[] = $this->generateDependencyTests($analysis, $framework);

        return implode("\n\n", array_filter($tests));
    }

    /**
     * Extract widget name from code.
     */
    private function extractWidgetName(string $code): ?string
    {
        if (preg_match('/public function get_name\(\)\s*\{[^}]*return\s*[\'"]([^\'"]+)[\'"]/', $code, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract widget title from code.
     */
    private function extractWidgetTitle(string $code): ?string
    {
        if (preg_match('/public function get_title\(\)\s*\{[^}]*return\s*[\'"]([^\'"]+)[\'"]/', $code, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract widget icon from code.
     */
    private function extractWidgetIcon(string $code): ?string
    {
        if (preg_match('/public function get_icon\(\)\s*\{[^}]*return\s*[\'"]([^\'"]+)[\'"]/', $code, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract widget categories from code.
     */
    private function extractWidgetCategories(string $code): array
    {
        if (preg_match('/public function get_categories\(\)\s*\{[^}]*return\s*\[(.*?)\]/', $code, $matches)) {
            $categoriesString = $matches[1];
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $categoriesString, $categoryMatches);
            return $categoryMatches[1];
        }
        return [];
    }

    /**
     * Extract controls from the widget code.
     */
    private function extractControls(string $code): array
    {
        $controls = [];
        
        // Match add_control calls
        preg_match_all('/\$this->add_control\(\s*[\'"]([^\'"]+)[\'"],\s*\[(.*?)\]\s*\);/s', $code, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $controlId = $match[1];
            $controlConfig = $match[2];
            
            $control = [
                'id' => $controlId,
                'type' => $this->extractControlType($controlConfig),
                'label' => $this->extractControlLabel($controlConfig),
                'default' => $this->extractControlDefault($controlConfig),
                'options' => $this->extractControlOptions($controlConfig),
                'condition' => $this->extractControlCondition($controlConfig),
            ];
            
            $controls[] = $control;
        }
        
        return $controls;
    }

    /**
     * Extract control sections from the widget code.
     */
    private function extractControlSections(string $code): array
    {
        $sections = [];
        
        preg_match_all('/\$this->start_controls_section\(\s*[\'"]([^\'"]+)[\'"],\s*\[(.*?)\]\s*\);/s', $code, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $sectionId = $match[1];
            $sectionConfig = $match[2];
            
            $section = [
                'id' => $sectionId,
                'label' => $this->extractControlLabel($sectionConfig),
                'tab' => $this->extractSectionTab($sectionConfig),
            ];
            
            $sections[] = $section;
        }
        
        return $sections;
    }

    /**
     * Check if the widget has a render method.
     */
    private function hasRenderMethod(string $code): bool
    {
        return preg_match('/protected function render\(\)/', $code) === 1;
    }

    /**
     * Extract style dependencies.
     */
    private function extractStyleDependencies(string $code): array
    {
        $dependencies = [];
        
        if (preg_match('/public function get_style_depends\(\)\s*\{[^}]*return\s*\[(.*?)\]/', $code, $matches)) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $depMatches);
            $dependencies = $depMatches[1];
        }
        
        return $dependencies;
    }

    /**
     * Extract script dependencies.
     */
    private function extractScriptDependencies(string $code): array
    {
        $dependencies = [];
        
        if (preg_match('/public function get_script_depends\(\)\s*\{[^}]*return\s*\[(.*?)\]/', $code, $matches)) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $depMatches);
            $dependencies = $depMatches[1];
        }
        
        return $dependencies;
    }

    /**
     * Extract control type from configuration.
     */
    private function extractControlType(string $config): ?string
    {
        if (preg_match('/[\'"]type[\'"][\s]*=>[\s]*Controls_Manager::([A-Z_]+)/', $config, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract control label from configuration.
     */
    private function extractControlLabel(string $config): ?string
    {
        if (preg_match('/[\'"]label[\'"][\s]*=>[\s]*[\'"]([^\'"]+)[\'"]/', $config, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract control default value from configuration.
     */
    private function extractControlDefault(string $config): mixed
    {
        if (preg_match('/[\'"]default[\'"][\s]*=>[\s]*[\'"]([^\'"]+)[\'"]/', $config, $matches)) {
            return $matches[1];
        }
        if (preg_match('/[\'"]default[\'"][\s]*=>[\s]*([^,\]]+)/', $config, $matches)) {
            $value = trim($matches[1]);
            if (is_numeric($value)) {
                return (int) $value;
            }
            if ($value === 'true') return true;
            if ($value === 'false') return false;
            return $value;
        }
        return null;
    }

    /**
     * Extract control options from configuration.
     */
    private function extractControlOptions(string $config): array
    {
        if (preg_match('/[\'"]options[\'"][\s]*=>[\s]*\[(.*?)\]/s', $config, $matches)) {
            $optionsString = $matches[1];
            $options = [];
            
            preg_match_all('/[\'"]([^\'"]+)[\'"][\s]*=>[\s]*[\'"]([^\'"]+)[\'"]/', $optionsString, $optionMatches, PREG_SET_ORDER);
            
            foreach ($optionMatches as $match) {
                $options[$match[1]] = $match[2];
            }
            
            return $options;
        }
        return [];
    }

    /**
     * Extract control condition from configuration.
     */
    private function extractControlCondition(string $config): array
    {
        if (preg_match('/[\'"]condition[\'"][\s]*=>[\s]*\[(.*?)\]/s', $config, $matches)) {
            $conditionString = $matches[1];
            $conditions = [];
            
            preg_match_all('/[\'"]([^\'"]+)[\'"][\s]*=>[\s]*[\'"]([^\'"]+)[\'"]/', $conditionString, $conditionMatches, PREG_SET_ORDER);
            
            foreach ($conditionMatches as $match) {
                $conditions[$match[1]] = $match[2];
            }
            
            return $conditions;
        }
        return [];
    }

    /**
     * Extract section tab from configuration.
     */
    private function extractSectionTab(string $config): ?string
    {
        if (preg_match('/[\'"]tab[\'"][\s]*=>[\s]*Controls_Manager::([A-Z_]+)/', $config, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Generate basic widget tests.
     */
    private function generateBasicWidgetTests(array $analysis, string $framework): string
    {
        $widgetName = $analysis['widget_name'] ?? 'test_widget';
        $widgetTitle = $analysis['widget_title'] ?? 'Test Widget';

        if ($framework === 'pest') {
            return <<<PHP
    test('widget has correct name', function () {
        \$widget = new TestWidget();
        expect(\$widget->get_name())->toBe('{$widgetName}');
    });

    test('widget has correct title', function () {
        \$widget = new TestWidget();
        expect(\$widget->get_title())->toBe('{$widgetTitle}');
    });

    test('widget has correct icon', function () {
        \$widget = new TestWidget();
        expect(\$widget->get_icon())->toBeString();
    });

    test('widget has categories', function () {
        \$widget = new TestWidget();
        expect(\$widget->get_categories())->toBeArray();
    });
PHP;
        } else {
            return <<<PHP
    public function test_widget_has_correct_name()
    {
        \$widget = new TestWidget();
        \$this->assertEquals('{$widgetName}', \$widget->get_name());
    }

    public function test_widget_has_correct_title()
    {
        \$widget = new TestWidget();
        \$this->assertEquals('{$widgetTitle}', \$widget->get_title());
    }

    public function test_widget_has_correct_icon()
    {
        \$widget = new TestWidget();
        \$this->assertIsString(\$widget->get_icon());
    }

    public function test_widget_has_categories()
    {
        \$widget = new TestWidget();
        \$this->assertIsArray(\$widget->get_categories());
    }
PHP;
        }
    }

    /**
     * Generate control tests.
     */
    private function generateControlTests(array $controls, string $framework): string
    {
        $tests = [];

        foreach ($controls as $control) {
            $controlId = $control['id'];
            $controlType = $control['type'] ?? 'TEXT';
            $defaultValue = $control['default'];

            if ($framework === 'pest') {
                $tests[] = <<<PHP
    test('control {$controlId} has correct default value', function () {
        \$widget = new TestWidget();
        \$settings = \$widget->get_settings_for_display();

        // Test default value assignment
        if (isset(\$settings['{$controlId}'])) {
            expect(\$settings['{$controlId}'])->toBe('{$defaultValue}');
        }
    });

    test('control {$controlId} renders correctly in frontend', function () {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => 'test_value']);

        ob_start();
        \$widget->render();
        \$output = ob_get_clean();

        expect(\$output)->toContain('test_value');
    });
PHP;
            } else {
                $tests[] = <<<PHP
    public function test_control_{$controlId}_default_value()
    {
        \$widget = new TestWidget();
        \$settings = \$widget->get_settings_for_display();

        // Test default value assignment
        if (isset(\$settings['{$controlId}'])) {
            \$this->assertEquals('{$defaultValue}', \$settings['{$controlId}']);
        }
    }

    public function test_control_{$controlId}_frontend_rendering()
    {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => 'test_value']);

        ob_start();
        \$widget->render();
        \$output = ob_get_clean();

        \$this->assertStringContainsString('test_value', \$output);
    }
PHP;
            }

            // Add validation tests for specific control types
            if ($controlType === 'COLOR') {
                $tests[] = $this->generateColorControlTest($controlId, $framework);
            } elseif ($controlType === 'MEDIA') {
                $tests[] = $this->generateMediaControlTest($controlId, $framework);
            } elseif ($controlType === 'SELECT') {
                $tests[] = $this->generateSelectControlTest($controlId, $control['options'] ?? [], $framework);
            }
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate control section tests.
     */
    private function generateControlSectionTests(array $sections, string $framework): string
    {
        $tests = [];

        foreach ($sections as $section) {
            $sectionId = $section['id'];
            $sectionLabel = $section['label'] ?? 'Test Section';

            if ($framework === 'pest') {
                $tests[] = <<<PHP
    test('control section {$sectionId} is properly registered', function () {
        \$widget = new TestWidget();
        \$controls = \$widget->get_controls();

        expect(\$controls)->toHaveKey('{$sectionId}');
        expect(\$controls['{$sectionId}']['label'])->toBe('{$sectionLabel}');
    });
PHP;
            } else {
                $tests[] = <<<PHP
    public function test_control_section_{$sectionId}_registration()
    {
        \$widget = new TestWidget();
        \$controls = \$widget->get_controls();

        \$this->assertArrayHasKey('{$sectionId}', \$controls);
        \$this->assertEquals('{$sectionLabel}', \$controls['{$sectionId}']['label']);
    }
PHP;
            }
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate render tests.
     */
    private function generateRenderTests(array $analysis, string $framework): string
    {
        if ($framework === 'pest') {
            return <<<PHP
    test('widget renders without errors', function () {
        \$widget = new TestWidget();

        ob_start();
        \$widget->render();
        \$output = ob_get_clean();

        expect(\$output)->toBeString();
    });

    test('widget renders with custom settings', function () {
        \$widget = new TestWidget();
        \$widget->set_settings([
            'title' => 'Custom Title',
            'content' => 'Custom Content'
        ]);

        ob_start();
        \$widget->render();
        \$output = ob_get_clean();

        expect(\$output)->toContain('Custom Title');
        expect(\$output)->toContain('Custom Content');
    });
PHP;
        } else {
            return <<<PHP
    public function test_widget_renders_without_errors()
    {
        \$widget = new TestWidget();

        ob_start();
        \$widget->render();
        \$output = ob_get_clean();

        \$this->assertIsString(\$output);
    }

    public function test_widget_renders_with_custom_settings()
    {
        \$widget = new TestWidget();
        \$widget->set_settings([
            'title' => 'Custom Title',
            'content' => 'Custom Content'
        ]);

        ob_start();
        \$widget->render();
        \$output = ob_get_clean();

        \$this->assertStringContainsString('Custom Title', \$output);
        \$this->assertStringContainsString('Custom Content', \$output);
    }
PHP;
        }
    }

    /**
     * Generate dependency tests.
     */
    private function generateDependencyTests(array $analysis, string $framework): string
    {
        $styleDeps = $analysis['style_dependencies'] ?? [];
        $scriptDeps = $analysis['script_dependencies'] ?? [];

        $tests = [];

        if (!empty($styleDeps)) {
            if ($framework === 'pest') {
                $tests[] = <<<PHP
    test('widget has correct style dependencies', function () {
        \$widget = new TestWidget();
        \$deps = \$widget->get_style_depends();

        expect(\$deps)->toBeArray();
        expect(\$deps)->toContain('{$styleDeps[0]}');
    });
PHP;
            } else {
                $tests[] = <<<PHP
    public function test_widget_style_dependencies()
    {
        \$widget = new TestWidget();
        \$deps = \$widget->get_style_depends();

        \$this->assertIsArray(\$deps);
        \$this->assertContains('{$styleDeps[0]}', \$deps);
    }
PHP;
            }
        }

        if (!empty($scriptDeps)) {
            if ($framework === 'pest') {
                $tests[] = <<<PHP
    test('widget has correct script dependencies', function () {
        \$widget = new TestWidget();
        \$deps = \$widget->get_script_depends();

        expect(\$deps)->toBeArray();
        expect(\$deps)->toContain('{$scriptDeps[0]}');
    });
PHP;
            } else {
                $tests[] = <<<PHP
    public function test_widget_script_dependencies()
    {
        \$widget = new TestWidget();
        \$deps = \$widget->get_script_depends();

        \$this->assertIsArray(\$deps);
        \$this->assertContains('{$scriptDeps[0]}', \$deps);
    }
PHP;
            }
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate color control specific test.
     */
    private function generateColorControlTest(string $controlId, string $framework): string
    {
        if ($framework === 'pest') {
            return <<<PHP
    test('control {$controlId} validates color format', function () {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => '#ff0000']);

        \$settings = \$widget->get_settings_for_display();
        expect(\$settings['{$controlId}'])->toMatch('/^#[a-fA-F0-9]{6}$/');
    });
PHP;
        } else {
            return <<<PHP
    public function test_control_{$controlId}_color_validation()
    {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => '#ff0000']);

        \$settings = \$widget->get_settings_for_display();
        \$this->assertMatchesRegularExpression('/^#[a-fA-F0-9]{6}$/', \$settings['{$controlId}']);
    }
PHP;
        }
    }

    /**
     * Generate media control specific test.
     */
    private function generateMediaControlTest(string $controlId, string $framework): string
    {
        if ($framework === 'pest') {
            return <<<PHP
    test('control {$controlId} handles media attachment', function () {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => ['id' => 123, 'url' => 'http://example.com/image.jpg']]);

        \$settings = \$widget->get_settings_for_display();
        expect(\$settings['{$controlId}']['id'])->toBe(123);
        expect(\$settings['{$controlId}']['url'])->toContain('image.jpg');
    });
PHP;
        } else {
            return <<<PHP
    public function test_control_{$controlId}_media_handling()
    {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => ['id' => 123, 'url' => 'http://example.com/image.jpg']]);

        \$settings = \$widget->get_settings_for_display();
        \$this->assertEquals(123, \$settings['{$controlId}']['id']);
        \$this->assertStringContainsString('image.jpg', \$settings['{$controlId}']['url']);
    }
PHP;
        }
    }

    /**
     * Generate select control specific test.
     */
    private function generateSelectControlTest(string $controlId, array $options, string $framework): string
    {
        $firstOption = !empty($options) ? array_keys($options)[0] : 'option1';

        if ($framework === 'pest') {
            return <<<PHP
    test('control {$controlId} validates select options', function () {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => '{$firstOption}']);

        \$settings = \$widget->get_settings_for_display();
        expect(\$settings['{$controlId}'])->toBe('{$firstOption}');
    });
PHP;
        } else {
            return <<<PHP
    public function test_control_{$controlId}_select_validation()
    {
        \$widget = new TestWidget();
        \$widget->set_settings(['{$controlId}' => '{$firstOption}']);

        \$settings = \$widget->get_settings_for_display();
        \$this->assertEquals('{$firstOption}', \$settings['{$controlId}']);
    }
PHP;
        }
    }
}
