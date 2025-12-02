<?php

namespace Database\Seeders;

use App\Models\DocumentationArticle;
use App\Models\DocumentationCategory;
use App\Models\DocumentationRouteMapping;
use Illuminate\Database\Seeder;

class DocumentationContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates all documentation categories, articles, and route mappings.
     */
    public function run(): void
    {
        $this->command->info('Seeding documentation content...');

        // Create categories
        $categories = $this->createCategories();
        $this->command->info('Created '.count($categories).' categories');

        // Create articles
        $articleCount = 0;
        $routeCount = 0;

        foreach ($this->getArticles() as $categorySlug => $articles) {
            $category = $categories[$categorySlug] ?? null;
            if (! $category) {
                $this->command->warn("Category not found: {$categorySlug}");

                continue;
            }

            foreach ($articles as $articleData) {
                $routes = $articleData['routes'] ?? [];
                unset($articleData['routes']);

                $article = DocumentationArticle::updateOrCreate(
                    ['slug' => $articleData['slug']],
                    array_merge($articleData, [
                        'category_id' => $category->id,
                        'status' => 'published',
                        'is_internal' => false,
                        'show_in_help_widget' => true,
                        'published_at' => now(),
                    ])
                );

                $articleCount++;

                // Create route mappings
                foreach ($routes as $routeName => $routeLabel) {
                    DocumentationRouteMapping::updateOrCreate(
                        ['route_name' => $routeName, 'article_id' => $article->id],
                        ['route_label' => $routeLabel]
                    );
                    $routeCount++;
                }
            }
        }

        $this->command->info("Created {$articleCount} articles with {$routeCount} route mappings");
    }

    /**
     * Create documentation categories.
     */
    private function createCategories(): array
    {
        $categoriesData = [
            'getting-started' => [
                'name' => 'Getting Started',
                'description' => 'Introduction to CAP TRACK and basic setup guides',
                'icon' => 'rocket-launch',
                'sort_order' => 1,
            ],
            'dashboard' => [
                'name' => 'Dashboard',
                'description' => 'Understanding your dashboard and key metrics',
                'icon' => 'home',
                'sort_order' => 2,
            ],
            'participants-households' => [
                'name' => 'Participants & Households',
                'description' => 'Managing participant data, households, and enrollments',
                'icon' => 'users',
                'sort_order' => 3,
            ],
            'programs-services' => [
                'name' => 'Programs & Services',
                'description' => 'Program management, services, and work plans',
                'icon' => 'folder',
                'sort_order' => 4,
            ],
            'contracts-funders' => [
                'name' => 'Contracts & Funders',
                'description' => 'Contract management, due dates, and funder information',
                'icon' => 'document-text',
                'sort_order' => 5,
            ],
            'housing-projects' => [
                'name' => 'Housing & Projects',
                'description' => 'Property management, contractors, and housing jobs',
                'icon' => 'home-modern',
                'sort_order' => 6,
            ],
            'approvals' => [
                'name' => 'Approvals',
                'description' => 'Document and task approval workflows',
                'icon' => 'clipboard-document-check',
                'sort_order' => 7,
            ],
            'reports' => [
                'name' => 'Reports',
                'description' => 'CSBG reporting and data exports',
                'icon' => 'chart-bar',
                'sort_order' => 8,
            ],
            'accounting-fiscal' => [
                'name' => 'Accounting & Fiscal',
                'description' => 'DonorPerfect, Sage Intacct, and financial integrations',
                'icon' => 'currency-dollar',
                'sort_order' => 9,
            ],
            'administration' => [
                'name' => 'Administration',
                'description' => 'System settings, users, roles, and configuration',
                'icon' => 'cog-6-tooth',
                'sort_order' => 10,
            ],
            'user-guide' => [
                'name' => 'User Guide',
                'description' => 'Personal settings, profile, and account management',
                'icon' => 'user',
                'sort_order' => 11,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $slug => $data) {
            $categories[$slug] = DocumentationCategory::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, ['active' => true])
            );
        }

        return $categories;
    }

    /**
     * Get all articles organized by category.
     */
    private function getArticles(): array
    {
        return [
            'getting-started' => $this->getGettingStartedArticles(),
            'dashboard' => $this->getDashboardArticles(),
            'participants-households' => $this->getParticipantsArticles(),
            'programs-services' => $this->getProgramsArticles(),
            'contracts-funders' => $this->getContractsArticles(),
            'housing-projects' => $this->getHousingArticles(),
            'approvals' => $this->getApprovalsArticles(),
            'reports' => $this->getReportsArticles(),
            'accounting-fiscal' => $this->getAccountingArticles(),
            'administration' => $this->getAdministrationArticles(),
            'user-guide' => $this->getUserGuideArticles(),
        ];
    }

    private function getGettingStartedArticles(): array
    {
        return [
            [
                'slug' => 'welcome-to-cap-track',
                'title' => 'Welcome to CAP TRACK',
                'summary' => 'An introduction to CAP TRACK - your comprehensive data management platform',
                'sort_order' => 1,
                'routes' => ['dashboard' => 'Dashboard'],
                'content' => $this->getArticleContent('welcome-to-cap-track'),
            ],
            [
                'slug' => 'navigating-cap-track',
                'title' => 'Navigating CAP TRACK',
                'summary' => 'Learn how to navigate the CAP TRACK interface and find what you need',
                'sort_order' => 2,
                'content' => $this->getArticleContent('navigating-cap-track'),
            ],
            [
                'slug' => 'your-first-steps',
                'title' => 'Your First Steps',
                'summary' => 'A quick-start guide for new users to get productive quickly',
                'sort_order' => 3,
                'content' => $this->getArticleContent('your-first-steps'),
            ],
        ];
    }

    private function getDashboardArticles(): array
    {
        return [
            [
                'slug' => 'dashboard-overview',
                'title' => 'Dashboard Overview',
                'summary' => 'Understanding your CAP TRACK dashboard and key metrics',
                'sort_order' => 1,
                'routes' => ['dashboard' => 'Dashboard'],
                'content' => $this->getArticleContent('dashboard-overview'),
            ],
            [
                'slug' => 'dashboard-tabs',
                'title' => 'Dashboard Tabs',
                'summary' => 'Understanding the four main dashboard tabs and their content',
                'sort_order' => 2,
                'routes' => ['dashboard' => 'Dashboard'],
                'content' => $this->getArticleContent('dashboard-tabs'),
            ],
            [
                'slug' => 'contract-dashboard',
                'title' => 'Contract Dashboard',
                'summary' => 'The specialized dashboard view for contract management users',
                'sort_order' => 3,
                'content' => $this->getArticleContent('contract-dashboard'),
            ],
        ];
    }

    private function getParticipantsArticles(): array
    {
        return [
            [
                'slug' => 'participants-households-hub',
                'title' => 'Participants & Households Hub',
                'summary' => 'Central hub for managing participants and households',
                'sort_order' => 1,
                'routes' => ['participant.household.hub' => 'Participants & Households Hub'],
                'content' => $this->getArticleContent('participants-households-hub'),
            ],
            [
                'slug' => 'participant-intake',
                'title' => 'Adding New Participants (Intake)',
                'summary' => 'Step-by-step guide to the 6-step participant intake wizard',
                'sort_order' => 2,
                'content' => $this->getArticleContent('participant-intake'),
            ],
            [
                'slug' => 'managing-participants',
                'title' => 'Managing Participants',
                'summary' => 'How to search, view, edit, and manage participant records',
                'sort_order' => 3,
                'routes' => ['participant.management' => 'Participant Management'],
                'content' => $this->getArticleContent('managing-participants'),
            ],
            [
                'slug' => 'managing-households',
                'title' => 'Managing Households',
                'summary' => 'How to manage household records, members, and address history',
                'sort_order' => 4,
                'routes' => ['household.management' => 'Household Management'],
                'content' => $this->getArticleContent('managing-households'),
            ],
            [
                'slug' => 'participant-import-export',
                'title' => 'Import & Export Data',
                'summary' => 'How to bulk import and export participant and household data',
                'sort_order' => 5,
                'content' => $this->getArticleContent('participant-import-export'),
            ],
            [
                'slug' => 'csbg-data-fields',
                'title' => 'CSBG Data Fields',
                'summary' => 'Understanding CSBG-compliant data fields and automatic calculations',
                'sort_order' => 6,
                'content' => $this->getArticleContent('csbg-data-fields'),
            ],
        ];
    }

    private function getProgramsArticles(): array
    {
        return [
            [
                'slug' => 'programs-services-hub',
                'title' => 'Programs & Services Hub',
                'summary' => 'Central hub for managing programs, services, and work plans',
                'sort_order' => 1,
                'routes' => ['program.service.hub' => 'Programs & Services Hub'],
                'content' => $this->getArticleContent('programs-services-hub'),
            ],
            [
                'slug' => 'program-management',
                'title' => 'Program Management',
                'summary' => 'Creating and managing programs in CAP TRACK',
                'sort_order' => 2,
                'routes' => ['program.management' => 'Program Management'],
                'content' => $this->getArticleContent('program-management'),
            ],
            [
                'slug' => 'service-management',
                'title' => 'Service Management',
                'summary' => 'Creating and managing services within programs',
                'sort_order' => 3,
                'routes' => ['service.management' => 'Service Management'],
                'content' => $this->getArticleContent('service-management'),
            ],
            [
                'slug' => 'work-plan-management',
                'title' => 'Work Plan Management',
                'summary' => 'Creating and managing annual work plans with goals',
                'sort_order' => 4,
                'content' => $this->getArticleContent('work-plan-management'),
            ],
            [
                'slug' => 'program-service-enrollments',
                'title' => 'Program & Service Enrollments',
                'summary' => 'How to enroll participants in programs and services',
                'sort_order' => 5,
                'content' => $this->getArticleContent('program-service-enrollments'),
            ],
        ];
    }

    private function getContractsArticles(): array
    {
        return [
            [
                'slug' => 'contract-management-overview',
                'title' => 'Contract Management Overview',
                'summary' => 'Managing contracts, due dates, and report submissions',
                'sort_order' => 1,
                'routes' => ['contract.management' => 'Contract Management'],
                'content' => $this->getArticleContent('contract-management-overview'),
            ],
            [
                'slug' => 'creating-contracts',
                'title' => 'Creating and Editing Contracts',
                'summary' => 'How to add and manage contract records',
                'sort_order' => 2,
                'content' => $this->getArticleContent('creating-contracts'),
            ],
            [
                'slug' => 'due-dates-reminders',
                'title' => 'Due Dates and Reminders',
                'summary' => 'Managing report due dates and automated email reminders',
                'sort_order' => 3,
                'content' => $this->getArticleContent('due-dates-reminders'),
            ],
            [
                'slug' => 'notification-recipients',
                'title' => 'Notification Recipients',
                'summary' => 'Managing users and distribution lists for contract notifications',
                'sort_order' => 4,
                'routes' => ['notification-recipients.management' => 'Notification Recipients'],
                'content' => $this->getArticleContent('notification-recipients'),
            ],
            [
                'slug' => 'funder-management',
                'title' => 'Funder Management',
                'summary' => 'Managing funding organization records',
                'sort_order' => 5,
                'routes' => ['funder.management' => 'Funder Management'],
                'content' => $this->getArticleContent('funder-management'),
            ],
        ];
    }

    private function getHousingArticles(): array
    {
        return [
            [
                'slug' => 'housing-projects-overview',
                'title' => 'Housing & Projects Overview',
                'summary' => 'Managing housing rehabilitation projects from start to finish',
                'sort_order' => 1,
                'routes' => ['projects.management' => 'Projects Management'],
                'content' => $this->getArticleContent('housing-projects-overview'),
            ],
            [
                'slug' => 'property-management',
                'title' => 'Property Management',
                'summary' => 'Managing property records for housing projects',
                'sort_order' => 2,
                'routes' => ['property.management' => 'Property Management'],
                'content' => $this->getArticleContent('property-management'),
            ],
            [
                'slug' => 'contractor-management',
                'title' => 'Contractor Management',
                'summary' => 'Managing contractor profiles and compliance documents',
                'sort_order' => 3,
                'routes' => ['contractor.management' => 'Contractor Management'],
                'content' => $this->getArticleContent('contractor-management'),
            ],
            [
                'slug' => 'housing-jobs-projects',
                'title' => 'Housing Jobs & Projects',
                'summary' => 'Creating and managing housing rehabilitation projects',
                'sort_order' => 4,
                'routes' => ['housing-job.management' => 'Housing Job Management'],
                'content' => $this->getArticleContent('housing-jobs-projects'),
            ],
            [
                'slug' => 'project-milestones-tasks',
                'title' => 'Milestones, Tasks & Documents',
                'summary' => 'Managing project phases, tasks, and document requirements',
                'sort_order' => 5,
                'content' => $this->getArticleContent('project-milestones-tasks'),
            ],
            [
                'slug' => 'communications-log',
                'title' => 'Communications Log',
                'summary' => 'Tracking all communications related to housing projects',
                'sort_order' => 6,
                'routes' => ['communications-log.management' => 'Communications Log'],
                'content' => $this->getArticleContent('communications-log'),
            ],
        ];
    }

    private function getApprovalsArticles(): array
    {
        return [
            [
                'slug' => 'approvals-overview',
                'title' => 'Approvals Overview',
                'summary' => 'Understanding the document and task approval workflow',
                'sort_order' => 1,
                'routes' => ['approvals.pending' => 'Pending Approvals'],
                'content' => $this->getArticleContent('approvals-overview'),
            ],
            [
                'slug' => 'approving-rejecting-items',
                'title' => 'Approving and Rejecting Items',
                'summary' => 'How to review and approve or reject pending items',
                'sort_order' => 2,
                'content' => $this->getArticleContent('approving-rejecting-items'),
            ],
        ];
    }

    private function getReportsArticles(): array
    {
        return [
            [
                'slug' => 'csbg-reports',
                'title' => 'CSBG Reports',
                'summary' => 'Generating CSBG Module 4 Section C compliance reports',
                'sort_order' => 1,
                'routes' => ['reports' => 'Reports'],
                'content' => $this->getArticleContent('csbg-reports'),
            ],
        ];
    }

    private function getAccountingArticles(): array
    {
        return [
            [
                'slug' => 'donorperfect-gifts',
                'title' => 'DonorPerfect Gifts',
                'summary' => 'Viewing and managing gift data from DonorPerfect',
                'sort_order' => 1,
                'routes' => ['donor-gifts.feed' => 'DonorPerfect Gifts'],
                'content' => $this->getArticleContent('donorperfect-gifts'),
            ],
            [
                'slug' => 'donorperfect-configuration',
                'title' => 'DonorPerfect Configuration',
                'summary' => 'Configuring the DonorPerfect API connection',
                'sort_order' => 2,
                'routes' => ['donor-gifts.configuration' => 'DonorPerfect Configuration'],
                'content' => $this->getArticleContent('donorperfect-configuration'),
            ],
            [
                'slug' => 'sage-intacct-sync',
                'title' => 'Sage Intacct Sync',
                'summary' => 'Syncing gift data to Sage Intacct accounting system',
                'sort_order' => 3,
                'routes' => ['sage-intacct.configuration' => 'Sage Intacct Configuration'],
                'content' => $this->getArticleContent('sage-intacct-sync'),
            ],
            [
                'slug' => 'check-images',
                'title' => 'Check Images',
                'summary' => 'Viewing and exporting check scan images',
                'sort_order' => 4,
                'content' => $this->getArticleContent('check-images'),
            ],
        ];
    }

    private function getAdministrationArticles(): array
    {
        return [
            [
                'slug' => 'admin-panel-overview',
                'title' => 'Admin Panel Overview',
                'summary' => 'Central administration hub for CAP TRACK',
                'sort_order' => 1,
                'routes' => ['admin.panel' => 'Admin Panel'],
                'content' => $this->getArticleContent('admin-panel-overview'),
            ],
            [
                'slug' => 'user-management',
                'title' => 'User Management',
                'summary' => 'Creating and managing user accounts',
                'sort_order' => 2,
                'routes' => ['user.management' => 'User Management'],
                'content' => $this->getArticleContent('user-management'),
            ],
            [
                'slug' => 'roles-permissions',
                'title' => 'Roles & Permissions',
                'summary' => 'Managing role-based access control',
                'sort_order' => 3,
                'routes' => ['role-permission.management' => 'Roles & Permissions'],
                'content' => $this->getArticleContent('roles-permissions'),
            ],
            [
                'slug' => 'reference-data',
                'title' => 'Reference Data Management',
                'summary' => 'Managing dropdown values and data versioning',
                'sort_order' => 4,
                'content' => $this->getArticleContent('reference-data'),
            ],
            [
                'slug' => 'login-audit-log',
                'title' => 'Login Audit Log',
                'summary' => 'Monitoring user authentication events',
                'sort_order' => 5,
                'routes' => ['audit.login' => 'Login Audit Log'],
                'content' => $this->getArticleContent('login-audit-log'),
            ],
            [
                'slug' => 'poverty-guidelines',
                'title' => 'Poverty Guidelines',
                'summary' => 'Managing federal poverty level guidelines',
                'sort_order' => 6,
                'content' => $this->getArticleContent('poverty-guidelines'),
            ],
        ];
    }

    private function getUserGuideArticles(): array
    {
        return [
            [
                'slug' => 'profile-settings',
                'title' => 'Profile Settings',
                'summary' => 'Managing your personal profile and account settings',
                'sort_order' => 1,
                'routes' => ['settings.profile' => 'Profile Settings'],
                'content' => $this->getArticleContent('profile-settings'),
            ],
            [
                'slug' => 'password-management',
                'title' => 'Password Management',
                'summary' => 'Changing your password securely',
                'sort_order' => 2,
                'content' => $this->getArticleContent('password-management'),
            ],
            [
                'slug' => 'appearance-settings',
                'title' => 'Appearance Settings',
                'summary' => 'Customizing the look and feel of CAP TRACK',
                'sort_order' => 3,
                'content' => $this->getArticleContent('appearance-settings'),
            ],
            [
                'slug' => 'using-help-feedback',
                'title' => 'Using Help & Feedback',
                'summary' => 'Getting help and providing feedback on CAP TRACK',
                'sort_order' => 4,
                'content' => $this->getArticleContent('using-help-feedback'),
            ],
            [
                'slug' => 'tips-shortcuts',
                'title' => 'Tips & Best Practices',
                'summary' => 'Tips for using CAP TRACK efficiently',
                'sort_order' => 5,
                'content' => $this->getArticleContent('tips-shortcuts'),
            ],
        ];
    }

    /**
     * Get article content by slug.
     * All content is stored here for full reproducibility.
     */
    private function getArticleContent(string $slug): string
    {
        return $this->getAllArticleContent()[$slug] ?? '<p>Documentation content for this article.</p>';
    }

    /**
     * Get all article content.
     */
    private function getAllArticleContent(): array
    {
        return [
            // Getting Started
            'welcome-to-cap-track' => '<h2>Welcome to CAP TRACK</h2>
<p>CAP TRACK is a comprehensive data management platform designed specifically for Community Action Partnership agencies. The system streamlines participant and household data management, program services tracking, and CSBG (Community Services Block Grant) reporting requirements.</p>

<h3>What is CAP TRACK?</h3>
<p>CAP TRACK provides a centralized platform for managing all aspects of your agency\'s operations:</p>
<ul>
<li><strong>Participant & Household Management</strong> - Track individuals and families you serve with complete demographic and service history</li>
<li><strong>Program & Service Tracking</strong> - Manage programs, services, work plans, and enrollment data</li>
<li><strong>Contract Management</strong> - Track contracts, due dates, and funder relationships</li>
<li><strong>Housing & Project Management</strong> - Coordinate housing rehabilitation projects from intake to completion</li>
<li><strong>CSBG Compliance</strong> - Generate Module 4 Section C reports with automated data aggregation</li>
<li><strong>Financial Integration</strong> - Sync with DonorPerfect and Sage Intacct for seamless accounting</li>
</ul>

<h3>Key Benefits</h3>
<ul>
<li><strong>Centralized Data</strong> - All participant, program, and contract data in one place</li>
<li><strong>CSBG Compliant</strong> - Built-in support for federal reporting requirements</li>
<li><strong>Role-Based Access</strong> - Granular permissions ensure data security</li>
<li><strong>Real-Time Reporting</strong> - Generate reports instantly with current data</li>
<li><strong>Audit Trails</strong> - Complete tracking of all data changes and user actions</li>
</ul>

<h3>Getting Help</h3>
<p>Throughout CAP TRACK, you\'ll find contextual help available:</p>
<ul>
<li><strong>Help Button</strong> - Click the floating help button (?) on any page to see relevant documentation</li>
<li><strong>Documentation</strong> - Access the full documentation from the sidebar</li>
<li><strong>Feedback</strong> - Use the feedback button to report issues or suggest improvements</li>
</ul>',

            'navigating-cap-track' => '<h2>Navigating CAP TRACK</h2>
<p>CAP TRACK uses a clean, intuitive interface designed for efficiency. Here\'s how to get around:</p>

<h3>The Sidebar</h3>
<p>The left sidebar is your main navigation hub. It\'s organized into logical sections:</p>
<ul>
<li><strong>Platform</strong> - Your Dashboard home</li>
<li><strong>Data Management</strong> - Participants & Households</li>
<li><strong>Programs</strong> - Programs & Work Plans</li>
<li><strong>Contracts</strong> - Contract Management & Notifications</li>
<li><strong>Housing & Projects</strong> - Properties, Contractors, Projects, Communications</li>
<li><strong>Approvals</strong> - Pending approval items</li>
<li><strong>Reports</strong> - Reporting Dashboard</li>
<li><strong>Accounting & Fiscal</strong> - DonorPerfect, Sage Intacct integrations</li>
<li><strong>Administration</strong> - Admin Panel, User Management, Settings</li>
</ul>

<h3>What You See Depends on Your Role</h3>
<p>CAP TRACK uses role-based permissions. You\'ll only see menu items and features you have access to. If you need access to something you can\'t see, contact your administrator.</p>

<h3>User Menu</h3>
<p>Click your name/avatar at the bottom of the sidebar to access:</p>
<ul>
<li><strong>Settings</strong> - Update your profile, password, and appearance preferences</li>
<li><strong>Log Out</strong> - Securely end your session</li>
</ul>

<h3>Common Interface Elements</h3>
<ul>
<li><strong>Search Bars</strong> - Type to filter lists in real-time</li>
<li><strong>Pagination</strong> - Navigate through large data sets</li>
<li><strong>Action Buttons</strong> - Create, Edit, View, Delete items</li>
<li><strong>Status Badges</strong> - Color-coded indicators (green = active, red = inactive, etc.)</li>
<li><strong>Modals</strong> - Pop-up forms for creating and editing</li>
</ul>

<h3>Dark Mode</h3>
<p>CAP TRACK supports light and dark themes. Change your preference in Settings > Appearance.</p>',

            'your-first-steps' => '<h2>Your First Steps in CAP TRACK</h2>
<p>Welcome! This guide will help you get started with the most common tasks.</p>

<h3>1. Check Your Dashboard</h3>
<p>After logging in, you\'ll see your Dashboard. This shows:</p>
<ul>
<li>Key statistics (participants, enrollments, services delivered)</li>
<li>Your assigned programs</li>
<li>Recent activity</li>
<li>Upcoming deadlines</li>
</ul>

<h3>2. Review Your Programs</h3>
<p>Navigate to <strong>Programs & Work Plans</strong> to see which programs you\'re assigned to. You can only manage participants enrolled in your programs (unless you\'re an administrator).</p>

<h3>3. Add a New Participant</h3>
<p>To add a participant:</p>
<ol>
<li>Go to <strong>Participants & Households</strong></li>
<li>Click <strong>Participant Intake</strong></li>
<li>Follow the 6-step wizard:
    <ul>
    <li>Step 1: Household information</li>
    <li>Step 2: Head of household details</li>
    <li>Step 3: Demographics</li>
    <li>Step 4: Health & employment</li>
    <li>Step 5: Income & benefits</li>
    <li>Step 6: Program enrollment</li>
    </ul>
</li>
</ol>

<h3>4. Enroll in Programs & Services</h3>
<p>From a participant\'s profile, you can:</p>
<ul>
<li>Enroll them in programs</li>
<li>Record service enrollments</li>
<li>Track hours and sessions</li>
</ul>

<h3>5. Generate Reports</h3>
<p>Go to <strong>Reports</strong> to generate CSBG Module 4 Section C reports. Select your date range and filters to see aggregated data.</p>

<h3>Need Help?</h3>
<p>Click the <strong>Help</strong> button (?) on any page for contextual documentation, or browse the full documentation from the sidebar.</p>',

            // Dashboard
            'dashboard-overview' => '<h2>Dashboard Overview</h2>
<p>The Dashboard is your home base in CAP TRACK. It provides a comprehensive view of your agency\'s operations at a glance.</p>

<h3>Welcome Section</h3>
<p>At the top, you\'ll see:</p>
<ul>
<li>Your name and avatar</li>
<li>Your assigned roles</li>
<li>Number of programs you have access to</li>
<li>Current date</li>
</ul>

<h3>Date Range Filter</h3>
<p>Use the date range buttons to filter all dashboard metrics:</p>
<ul>
<li><strong>This Month</strong> - Current calendar month</li>
<li><strong>Last Month</strong> - Previous calendar month</li>
<li><strong>This Quarter</strong> - Current quarter</li>
<li><strong>This Year</strong> - Current calendar year</li>
<li><strong>Custom Range</strong> - Select specific start and end dates</li>
</ul>

<h3>Statistics Cards</h3>
<p>Six key metrics are displayed as cards:</p>
<ol>
<li><strong>Total Participants</strong> - Count of participants with trend indicator</li>
<li><strong>Active Enrollments</strong> - Currently active program enrollments</li>
<li><strong>Services Delivered</strong> - Service enrollments in the selected period</li>
<li><strong>Goal Completion Rate</strong> - Percentage of work plan goals achieved</li>
<li><strong>Households Served</strong> - Distinct households served</li>
<li><strong>My Programs</strong> - Programs assigned to you</li>
</ol>

<h3>Auto-Refresh</h3>
<p>The dashboard automatically refreshes every 30 seconds to show the latest data.</p>',

            'dashboard-tabs' => '<h2>Dashboard Tabs</h2>
<p>The dashboard is organized into four tabs, each providing different insights into your data.</p>

<h3>Programs Tab</h3>
<p>Your default view showing:</p>
<ul>
<li><strong>Assigned Programs</strong> - Programs you have access to with enrollment and service counts</li>
<li><strong>Recent Participants</strong> - Latest participant activity in your programs</li>
<li><strong>Work Plan Goals</strong> - Progress on work plan goals with visual progress bars</li>
</ul>
<p>Click any program card to see more details.</p>

<h3>Participants Tab</h3>
<p>Focused on participant data:</p>
<ul>
<li><strong>Enrollment Statistics</strong> - Breakdown by enrollment status</li>
<li><strong>Participant List</strong> - Paginated list of participants you have access to</li>
<li><strong>Quick Links</strong> - Jump to full participant management</li>
</ul>

<h3>Work Plans Tab</h3>
<p>Track your work plan progress:</p>
<ul>
<li><strong>Statistics</strong> - Total work plans, goals, and completion rates</li>
<li><strong>Calendar Timeline</strong> - Visual timeline of work plans by year</li>
<li><strong>Goal Progress</strong> - Individual goals with progress bars</li>
<li><strong>Connected Services</strong> - Services linked to work plan goals</li>
</ul>

<h3>Schedule Tab</h3>
<p>Upcoming deadlines and reminders:</p>
<ul>
<li><strong>Contract Deadlines</strong> - Color-coded by urgency (red = urgent, yellow = soon, blue = upcoming)</li>
<li><strong>Custom Reminders</strong> - Personal task reminders</li>
</ul>

<h3>Quick Action Buttons</h3>
<p>Each tab displays relevant quick action buttons at the bottom for common tasks like creating participants, managing services, or viewing reports.</p>',

            'contract-dashboard' => '<h2>Contract Dashboard</h2>
<p>Users with the "Contract Database Dashboard" role see a specialized dashboard focused on contract management.</p>

<h3>Contract Metrics</h3>
<p>Four key metrics are displayed:</p>
<ul>
<li><strong>Upcoming Reports</strong> - Reports due in the coming period</li>
<li><strong>Overdue Reports</strong> - Reports past their due date (highlighted in red)</li>
<li><strong>Due This Week</strong> - Reports due within 7 days</li>
<li><strong>Submitted This Period</strong> - Reports submitted in the selected period</li>
</ul>

<h3>Main Sections</h3>

<h4>Recent Activity</h4>
<p>A feed of recent contract-related activity including submissions and updates.</p>

<h4>Report Statistics</h4>
<ul>
<li>Completion rate progress bar</li>
<li>Reports by status breakdown</li>
<li>Total contracts assigned</li>
</ul>

<h4>Overdue Reports</h4>
<p>If any reports are overdue, they\'re prominently displayed here with:</p>
<ul>
<li>Report name and contract</li>
<li>Days overdue</li>
<li>Quick access to submit</li>
</ul>

<h4>Upcoming Deadlines</h4>
<p>Reports due soon with days remaining countdown.</p>

<h4>Recently Submitted</h4>
<p>Table of recently submitted reports with status badges (approved, submitted, under review).</p>',

            // Participants & Households
            'participants-households-hub' => '<h2>Participants & Households Hub</h2>
<p>The Participants & Households Hub is your central location for managing all participant and household data in CAP TRACK.</p>

<h3>Accessing the Hub</h3>
<p>Navigate to <strong>Data Management > Participants & Households</strong> in the sidebar.</p>

<h3>Hub Features</h3>
<p>From the hub, you can access:</p>
<ul>
<li><strong>Participant Intake</strong> - Add new participants using the 6-step wizard</li>
<li><strong>Participant Management</strong> - Search, view, and edit existing participants</li>
<li><strong>Household Management</strong> - Manage household records and members</li>
<li><strong>Import/Export</strong> - Bulk data operations</li>
</ul>

<h3>Understanding Participants vs. Households</h3>
<table>
<tr><th>Aspect</th><th>Participant</th><th>Household</th></tr>
<tr><td>Focus</td><td>Individual person</td><td>Family unit</td></tr>
<tr><td>Key Data</td><td>Demographics, employment, health</td><td>Composition, housing, income</td></tr>
<tr><td>Identifier</td><td>Name + Household Code</td><td>7-character Household Code</td></tr>
<tr><td>Enrollment</td><td>Enrolls in programs/services</td><td>N/A (members enroll)</td></tr>
</table>

<h3>Data Privacy</h3>
<p>CAP TRACK protects sensitive participant data:</p>
<ul>
<li><strong>Public Fields</strong> - Name, DOB, contact info - visible to all users</li>
<li><strong>Sensitive Fields</strong> - Demographics, income, health - only visible if participant is enrolled in your programs</li>
</ul>
<p>You\'ll see a "Limited View" badge when viewing participants outside your program access.</p>',

            'participant-intake' => '<h2>Adding New Participants</h2>
<p>The Participant Intake wizard guides you through adding a new participant in 6 steps.</p>

<h3>Starting an Intake</h3>
<ol>
<li>Go to <strong>Participants & Households > Participant Intake</strong></li>
<li>Or click <strong>New Participant</strong> from the dashboard</li>
</ol>

<h3>Step 1: Household Information</h3>
<p>Choose whether to add to an existing household or create a new one:</p>
<ul>
<li><strong>Existing Household</strong> - Search by household code or head of household name</li>
<li><strong>New Household</strong> - Enter:
    <ul>
    <li>Household type (Family, Individual, etc.)</li>
    <li>Housing status</li>
    <li>Full address (street, city, state, ZIP, county)</li>
    </ul>
</li>
</ul>

<h3>Step 2: Head of Household</h3>
<p>Enter personal information for the head of household:</p>
<ul>
<li>First name, middle name, last name, suffix</li>
<li>Date of birth</li>
<li>Phone and email</li>
</ul>

<h3>Step 3: Demographics</h3>
<p>Required demographic data for CSBG compliance:</p>
<ul>
<li>Gender</li>
<li>Ethnicity</li>
<li>Race (can select multiple)</li>
<li>Education level</li>
<li>Military status</li>
<li>Disability status and types</li>
</ul>

<h3>Step 4: Health & Employment</h3>
<ul>
<li>Chronic health conditions</li>
<li>Work status (employed, unemployed, etc.)</li>
<li>Hours worked per week</li>
<li>Employment seeking status</li>
</ul>

<h3>Step 5: Income & Benefits</h3>
<ul>
<li>Employment income</li>
<li>Income sources</li>
<li>Other income sources</li>
<li>Non-cash benefits (SNAP, Medicaid, etc.)</li>
<li>Health insurance status and sources</li>
</ul>

<h3>Step 6: Program Enrollment</h3>
<ul>
<li>Select program(s) to enroll in</li>
<li>Optional: Select initial service enrollment</li>
</ul>

<h3>Duplicate Detection</h3>
<p>Before saving, the system checks for potential duplicates based on name and date of birth. You\'ll be prompted to confirm if a match is found.</p>',

            'managing-participants' => '<h2>Managing Participants</h2>
<p>The Participant Management screen lets you search, view, and edit participant records.</p>

<h3>Searching Participants</h3>
<p>Use the search bar to find participants by:</p>
<ul>
<li>First or last name</li>
<li>Email address</li>
<li>Household code</li>
</ul>
<p>Results update in real-time as you type.</p>

<h3>Viewing a Participant</h3>
<p>Click a participant\'s name to view their full profile, including:</p>
<ul>
<li>Personal information</li>
<li>Demographic data</li>
<li>Employment and income details</li>
<li>Program enrollments</li>
<li>Service history</li>
<li>Household information</li>
</ul>

<h3>Editing a Participant</h3>
<ol>
<li>Find the participant in the list</li>
<li>Click the <strong>Edit</strong> button</li>
<li>Update the necessary fields in the modal form</li>
<li>Click <strong>Save</strong></li>
</ol>
<p>CSBG computed fields (age group, income classification, disconnected youth) are automatically recalculated on save.</p>

<h3>Program & Service Enrollment</h3>
<p>From the participant list, expand a row to:</p>
<ul>
<li>View current program enrollments</li>
<li>Enroll in a new program</li>
<li>Record a service enrollment</li>
</ul>

<h3>Deleting a Participant</h3>
<p>To delete a participant:</p>
<ol>
<li>Click the <strong>Delete</strong> button</li>
<li>Confirm the deletion</li>
</ol>
<p><strong>Note:</strong> You cannot delete the head of household. Transfer headship first, or delete the entire household.</p>

<h3>Required Permissions</h3>
<ul>
<li><code>participants.view</code> - View participant list</li>
<li><code>participants.edit</code> - Edit participant information</li>
<li><code>participants.delete</code> - Delete participants</li>
<li><code>enrollments.edit</code> - Create enrollments</li>
</ul>',

            'managing-households' => '<h2>Managing Households</h2>
<p>Households group related participants (families) together. Each household has a unique 7-character code.</p>

<h3>Household Code</h3>
<p>The household code is automatically generated when creating a new household (e.g., "ABC123H"). This code is used throughout CAP TRACK to identify the household.</p>

<h3>Searching Households</h3>
<p>Search by:</p>
<ul>
<li>Household code</li>
<li>Head of household name</li>
</ul>

<h3>Viewing a Household</h3>
<p>Click a household to view:</p>
<ul>
<li><strong>Household Information</strong> - Type, size, housing status, income</li>
<li><strong>Members</strong> - All participants in the household</li>
<li><strong>Address History</strong> - Current and previous addresses</li>
<li><strong>Enrollments</strong> - Programs household members are enrolled in</li>
</ul>

<h3>Editing a Household</h3>
<p>Two edit options:</p>
<ul>
<li><strong>Quick Edit</strong> - Update type, size, housing status, income</li>
<li><strong>Full Edit</strong> - All fields plus address changes</li>
</ul>

<h3>Updating Address</h3>
<p>When you update a household\'s address:</p>
<ol>
<li>The current address is saved to history with an end date</li>
<li>The new address becomes current</li>
<li>You can add a reason for the change</li>
</ol>

<h3>Adding Household Members</h3>
<p>To add a new member to an existing household:</p>
<ol>
<li>Go to <strong>Participant Intake</strong></li>
<li>Select <strong>Existing Household</strong></li>
<li>Search for and select the household</li>
<li>Complete the intake for the new member</li>
</ol>

<h3>Head of Household</h3>
<p>Each household must have exactly one head of household:</p>
<ul>
<li>The head cannot be deleted without transferring headship</li>
<li>To change the head, edit the household and select a different member</li>
</ul>

<h3>Poverty Calculations</h3>
<p>The <strong>HHS Poverty Percentage</strong> is calculated based on:</p>
<ul>
<li>Household size</li>
<li>Annual income (monthly × 12)</li>
<li>Current federal poverty guidelines</li>
</ul>',

            'participant-import-export' => '<h2>Import & Export Data</h2>
<p>CAP TRACK supports bulk data operations for efficiency.</p>

<h3>Accessing Import/Export</h3>
<p>Go to <strong>Participants & Households > Import/Export</strong></p>

<h3>Exporting Data</h3>
<p>Export types available:</p>
<ul>
<li><strong>All Participants & Households</strong> - Complete dataset</li>
<li><strong>Specific Household</strong> - By household ID</li>
<li><strong>Date Range</strong> - By created or updated date</li>
<li><strong>Household Codes</strong> - Multiple codes (comma-separated)</li>
<li><strong>By Program</strong> - All participants in a program</li>
<li><strong>Program & Date Range</strong> - Combined filter</li>
</ul>
<p>Exports are generated as CSV files showing record count and file size.</p>

<h3>Importing Data</h3>
<h4>System Import</h4>
<ol>
<li>Download the template CSV</li>
<li>Fill in your data following the column headers</li>
<li>Select your reference data set (for dropdown validations)</li>
<li>Upload the CSV file</li>
<li>Review validation results</li>
<li>Process the import</li>
</ol>

<h4>Import Options</h4>
<ul>
<li><strong>Update existing records</strong> - If checked, matching records will be updated</li>
<li><strong>Skip records with errors</strong> - Continue importing valid records</li>
</ul>

<h4>Custom Import</h4>
<p>Use pre-configured import templates for specific data sources (e.g., RX Office, HMIS).</p>

<h3>Import History</h3>
<p>View past imports with:</p>
<ul>
<li>File name and timestamp</li>
<li>User who performed import</li>
<li>Records created/updated/skipped</li>
<li>Error details</li>
</ul>

<h3>Required Permissions</h3>
<ul>
<li><code>participants.import</code> - Import data</li>
<li><code>participants.export</code> - Export data</li>
</ul>',

            'csbg-data-fields' => '<h2>CSBG Data Fields</h2>
<p>CAP TRACK is designed for CSBG Module 4 Section C compliance. Several fields are automatically calculated.</p>

<h3>CSBG Age Groups</h3>
<p>Automatically calculated from date of birth:</p>
<ul>
<li>0-5 years</li>
<li>6-13 years</li>
<li>14-17 years</li>
<li>18-24 years</li>
<li>25-44 years</li>
<li>45-54 years</li>
<li>55-59 years</li>
<li>60-64 years</li>
<li>65-74 years</li>
<li>75+ years</li>
</ul>

<h3>Disconnected Youth</h3>
<p>Automatically flagged for participants ages 14-24 who are:</p>
<ul>
<li>Not currently employed</li>
<li>Not enrolled in education</li>
</ul>

<h3>Income Classification</h3>
<p>Based on household income sources, one of 8 classifications:</p>
<ul>
<li>No Income</li>
<li>Non-Cash Benefits Only</li>
<li>Income from Employment Only</li>
<li>Income from Employment and Other Income Source</li>
<li>Income from Employment and Non-Cash Benefits</li>
<li>Income from Employment, Other Income Source, and Non-Cash Benefits</li>
<li>Other Income Source Only</li>
<li>Other Income Source and Non-Cash Benefits</li>
</ul>

<h3>Poverty Level</h3>
<p>HHS poverty percentage calculated from:</p>
<ul>
<li>Annual household income</li>
<li>Household size</li>
<li>Current year\'s federal poverty guidelines</li>
</ul>

<h3>Reference Data</h3>
<p>Dropdown values for demographics come from the Reference Data system, which is versioned for historical accuracy. The system tracks which reference data set was active when a participant was created.</p>',

            // Programs & Services
            'programs-services-hub' => '<h2>Programs & Services Hub</h2>
<p>The Programs & Services Hub provides access to all program-related management functions.</p>

<h3>Accessing the Hub</h3>
<p>Navigate to <strong>Programs > Programs & Work Plans</strong> in the sidebar.</p>

<h3>Key Areas</h3>
<ul>
<li><strong>Program Management</strong> - Create and manage programs</li>
<li><strong>Service Management</strong> - Define services within programs</li>
<li><strong>Work Plan Management</strong> - Set annual goals and targets</li>
<li><strong>Work Plan Goals</strong> - Track individual goal progress</li>
</ul>

<h3>Understanding the Hierarchy</h3>
<pre>
Program
  ├── Services (multiple)
  ├── Work Plans (by year)
  │     └── Goals (linked to services)
  └── Enrollments
        └── Service Enrollments
</pre>

<h3>Program Access</h3>
<p>Users are assigned to specific programs. You can only see and manage data for programs you have access to, unless you are an administrator.</p>',

            'program-management' => '<h2>Program Management</h2>
<p>Programs are the top-level organizational unit in CAP TRACK. Each program represents a distinct service offering or grant.</p>

<h3>Creating a Program</h3>
<ol>
<li>Go to <strong>Program Management</strong></li>
<li>Click <strong>New Program</strong></li>
<li>Fill in the required fields:
    <ul>
    <li><strong>Name</strong> - Unique program name</li>
    <li><strong>Description</strong> - What the program does</li>
    <li><strong>Program Manager</strong> (optional) - Assigned user</li>
    <li><strong>Start/End Dates</strong> (optional) - Program duration</li>
    </ul>
</li>
<li>Set special options:
    <ul>
    <li><strong>Housing Module</strong> - Enable housing job tracking</li>
    <li><strong>Contract Management Only</strong> - Hide from participant enrollment</li>
    </ul>
</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Program Properties</h3>
<table>
<tr><th>Field</th><th>Description</th></tr>
<tr><td>Name</td><td>Unique identifier for the program</td></tr>
<tr><td>Description</td><td>Detailed program description (WYSIWYG)</td></tr>
<tr><td>Program Manager</td><td>User responsible for the program</td></tr>
<tr><td>Active</td><td>Whether the program accepts new enrollments</td></tr>
<tr><td>Housing Module</td><td>Enable housing/project features</td></tr>
<tr><td>Contract Only</td><td>Internal program not shown to participants</td></tr>
<tr><td>Start/End Date</td><td>Program operational period</td></tr>
<tr><td>Notes</td><td>Internal notes (WYSIWYG)</td></tr>
</table>

<h3>Viewing Program Details</h3>
<p>Click a program to view:</p>
<ul>
<li>Program information</li>
<li>Associated services</li>
<li>Associated contracts</li>
<li>Enrollment statistics</li>
</ul>

<h3>Editing and Deleting</h3>
<ul>
<li><strong>Edit</strong> - Update any program field</li>
<li><strong>Toggle Status</strong> - Activate/deactivate</li>
<li><strong>Delete</strong> - Only if no services or contracts exist</li>
</ul>

<h3>Required Permissions</h3>
<ul>
<li><code>programs.view</code> - View programs</li>
<li><code>programs.create</code> - Create programs</li>
<li><code>programs.edit</code> - Edit programs</li>
<li><code>programs.delete</code> - Delete programs</li>
</ul>',

            'service-management' => '<h2>Service Management</h2>
<p>Services are specific offerings within a program that participants can enroll in.</p>

<h3>Creating a Service</h3>
<ol>
<li>Go to <strong>Service Management</strong></li>
<li>Click <strong>New Service</strong></li>
<li>Fill in:
    <ul>
    <li><strong>Service Name</strong> - What the service is called</li>
    <li><strong>Program</strong> - Which program this belongs to</li>
    <li><strong>Description</strong> - Details about the service</li>
    <li><strong>Start Date</strong> - When service begins</li>
    <li><strong>End Date</strong> (optional) - When service ends</li>
    </ul>
</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Filtering by Program</h3>
<p>Use the program filter dropdown or add <code>?program={id}</code> to the URL to see services for a specific program only.</p>

<h3>Service Statistics</h3>
<p>Each service shows:</p>
<ul>
<li>Number of enrolled participants</li>
<li>Number of completed enrollments</li>
<li>Associated work plan goals</li>
</ul>

<h3>Viewing Service Details</h3>
<p>Click a service to see:</p>
<ul>
<li>Service information</li>
<li>Parent program details</li>
<li>Enrollment statistics</li>
<li>Recent enrollments (last 10)</li>
</ul>

<h3>Service Enrollment Tracking</h3>
<p>Service enrollments track:</p>
<ul>
<li>Enrollment date</li>
<li>Status (enrolled, in progress, completed)</li>
<li>Hours received</li>
<li>Sessions attended</li>
<li>Outcomes</li>
<li>Completion notes</li>
</ul>

<h3>Required Permissions</h3>
<ul>
<li><code>services.view</code> - View services</li>
<li><code>services.create</code> - Create services</li>
<li><code>services.edit</code> - Edit services</li>
<li><code>services.delete</code> - Delete services</li>
</ul>',

            'work-plan-management' => '<h2>Work Plan Management</h2>
<p>Work plans help you set annual targets and track progress toward service delivery goals.</p>

<h3>What is a Work Plan?</h3>
<p>A work plan is a yearly planning document that contains goals for service delivery. Each goal has a target number and tracks actual progress.</p>

<h3>Creating a Work Plan</h3>
<ol>
<li>Go to <strong>Work Plan Management</strong></li>
<li>Click <strong>New Work Plan</strong></li>
<li>Fill in:
    <ul>
    <li><strong>Name</strong> - Work plan title</li>
    <li><strong>Program</strong> - Which program this is for</li>
    <li><strong>Year</strong> - The year this plan covers</li>
    <li><strong>Description</strong> - Plan details</li>
    </ul>
</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Work Plan Status</h3>
<ul>
<li><strong>Active</strong> - Currently in use</li>
<li><strong>Inactive</strong> - Historical or paused</li>
</ul>

<h3>Adding Goals</h3>
<p>After creating a work plan, add goals:</p>
<ol>
<li>Go to <strong>Work Plan Goal Management</strong></li>
<li>Click <strong>New Goal</strong></li>
<li>Fill in:
    <ul>
    <li><strong>Goal Name</strong> - What you\'re measuring</li>
    <li><strong>Work Plan</strong> - Parent work plan</li>
    <li><strong>Service</strong> (optional) - Link to a specific service</li>
    <li><strong>Target Number</strong> - Goal amount</li>
    <li><strong>Actual Number</strong> - Current progress</li>
    </ul>
</li>
</ol>

<h3>Syncing Goals</h3>
<p>Goals linked to services can be automatically synced:</p>
<ul>
<li>Click <strong>Sync</strong> to update a single goal from service enrollment data</li>
<li>Click <strong>Sync All</strong> to update all goals in a work plan</li>
</ul>

<h3>Goal Progress</h3>
<p>Progress is color-coded:</p>
<ul>
<li><span style="color: red;">Red</span> - Below 75% of target</li>
<li><span style="color: orange;">Yellow</span> - 75-99% of target</li>
<li><span style="color: green;">Green</span> - 100% or more of target</li>
</ul>

<h3>Program-Wide Goals</h3>
<p>Goals without a service linked track enrollment across all services in the program.</p>',

            'program-service-enrollments' => '<h2>Program & Service Enrollments</h2>
<p>Enrollments connect participants to programs and services, tracking their journey through your agency\'s offerings.</p>

<h3>Enrollment Hierarchy</h3>
<pre>
Participant
  └── Program Enrollment (one per program)
        └── Service Enrollments (multiple per program enrollment)
</pre>

<h3>Creating a Program Enrollment</h3>
<ol>
<li>Go to <strong>Participant Management</strong></li>
<li>Find the participant</li>
<li>Expand their row to see enrollment options</li>
<li>Select a program to enroll in</li>
<li>Set the enrollment date</li>
<li>Click <strong>Enroll</strong></li>
</ol>

<h3>Program Enrollment Fields</h3>
<ul>
<li><strong>Enrollment Date</strong> - When they enrolled</li>
<li><strong>Status</strong> - Active, Inactive, Completed, Exited</li>
<li><strong>Exit Date</strong> - When they left (if applicable)</li>
<li><strong>Exit Reason</strong> - Why they exited</li>
<li><strong>Notes</strong> - Additional information</li>
</ul>

<h3>Creating a Service Enrollment</h3>
<p>Participants must have a program enrollment before enrolling in services:</p>
<ol>
<li>From the participant row, select <strong>Record Service</strong></li>
<li>Choose the program (filters available services)</li>
<li>Select the service</li>
<li>Enter service details</li>
</ol>

<h3>Service Enrollment Fields</h3>
<ul>
<li><strong>Enrollment Date</strong></li>
<li><strong>Status</strong> - Enrolled, In Progress, Completed, Cancelled, Dropped</li>
<li><strong>Hours Received</strong> - Time spent in service</li>
<li><strong>Sessions Attended</strong> - Number of sessions</li>
<li><strong>Outcomes</strong> - Results achieved</li>
<li><strong>Completion Date</strong></li>
<li><strong>Completion Notes</strong></li>
</ul>

<h3>Enrollment Status Flow</h3>
<pre>
Enrolled → In Progress → Completed
                       → Cancelled
                       → Dropped
</pre>

<h3>Viewing Enrollment History</h3>
<p>On a participant\'s profile, you can see all enrollments with:</p>
<ul>
<li>Program name</li>
<li>Days enrolled</li>
<li>Service count</li>
<li>Status</li>
</ul>',

            // Contracts & Funders
            'contract-management-overview' => '<h2>Contract Management Overview</h2>
<p>The Contract Management module helps you track grants, contracts, and reporting requirements.</p>

<h3>Accessing Contract Management</h3>
<p>Navigate to <strong>Contracts > Contract Management</strong> in the sidebar.</p>

<h3>Key Features</h3>
<ul>
<li><strong>Contract Tracking</strong> - Store contract details, dates, and amounts</li>
<li><strong>Due Date Management</strong> - Track report deadlines</li>
<li><strong>Automated Reminders</strong> - Email notifications before due dates</li>
<li><strong>Report Submission</strong> - Submit and track reports</li>
<li><strong>Document Storage</strong> - Attach contract documents</li>
</ul>

<h3>Contract List</h3>
<p>The main view shows all contracts with:</p>
<ul>
<li>Title and contract number</li>
<li>Associated program</li>
<li>Funder name</li>
<li>Status (Active, Expired, Upcoming, Archived)</li>
<li>Amount</li>
<li>Date range</li>
</ul>

<h3>Filtering Contracts</h3>
<p>Filter by:</p>
<ul>
<li>Grant code</li>
<li>Title</li>
<li>Program</li>
<li>Funder</li>
<li>Date range</li>
<li>Status</li>
</ul>

<h3>Two Tab Interface</h3>
<ul>
<li><strong>Contracts Tab</strong> - Main contract list and management</li>
<li><strong>Upcoming Deadlines Tab</strong> - Due dates across all contracts</li>
</ul>',

            'creating-contracts' => '<h2>Creating and Editing Contracts</h2>

<h3>Creating a New Contract</h3>
<ol>
<li>Go to <strong>Contract Management</strong></li>
<li>Click <strong>New Contract</strong></li>
<li>Fill in the required fields</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Contract Fields</h3>
<table>
<tr><th>Field</th><th>Description</th><th>Required</th></tr>
<tr><td>Title</td><td>Contract name/title</td><td>Yes</td></tr>
<tr><td>Contract Number</td><td>Official contract ID</td><td>No</td></tr>
<tr><td>CFDA Number</td><td>Catalog of Federal Domestic Assistance</td><td>No</td></tr>
<tr><td>Grant Code</td><td>Internal grant identifier</td><td>No</td></tr>
<tr><td>Program</td><td>Associated program</td><td>Yes</td></tr>
<tr><td>Funder</td><td>Funding organization</td><td>Yes</td></tr>
<tr><td>Purpose</td><td>Contract purpose (WYSIWYG)</td><td>No</td></tr>
<tr><td>Amount</td><td>Contract dollar amount</td><td>No</td></tr>
<tr><td>Date of Award</td><td>When contract was awarded</td><td>No</td></tr>
<tr><td>Start Date</td><td>Contract start</td><td>Yes</td></tr>
<tr><td>End Date</td><td>Contract end</td><td>Yes</td></tr>
<tr><td>Notes</td><td>Internal notes (WYSIWYG)</td><td>No</td></tr>
</table>

<h3>Contract Documents</h3>
<p>Attach documents to contracts:</p>
<ul>
<li>Supported types: PDF, Word, Excel, images</li>
<li>Maximum size: 50MB per file</li>
<li>Documents are stored securely</li>
</ul>

<h3>Contract Status</h3>
<p>Status is calculated automatically:</p>
<ul>
<li><strong>Active</strong> - Current date is between start and end</li>
<li><strong>Upcoming</strong> - Start date is in the future</li>
<li><strong>Expired</strong> - End date has passed</li>
<li><strong>Archived</strong> - Manually archived</li>
</ul>

<h3>Archiving Contracts</h3>
<p>Archive completed contracts to keep them for records without cluttering your active list. Archived contracts can be unarchived if needed.</p>

<h3>Required Permissions</h3>
<ul>
<li><code>contracts.view</code> - View contracts</li>
<li><code>contracts.create</code> - Create contracts</li>
<li><code>contracts.edit</code> - Edit contracts</li>
<li><code>contracts.delete</code> - Delete contracts</li>
</ul>',

            'due-dates-reminders' => '<h2>Due Dates and Reminders</h2>
<p>Track report deadlines and receive automated reminders before they\'re due.</p>

<h3>Adding Due Dates</h3>
<ol>
<li>Open a contract</li>
<li>Go to the <strong>Due Dates</strong> section</li>
<li>Click <strong>Add Due Date</strong></li>
<li>Fill in:
    <ul>
    <li><strong>Report Name</strong> - What report is due</li>
    <li><strong>Report Type</strong> - Monthly, Quarterly, Semi-Annual, Annual, Final, Special</li>
    <li><strong>Due Date</strong> - When it\'s due</li>
    <li><strong>Due Time</strong> (optional) - Specific time</li>
    <li><strong>Description</strong> - Additional details</li>
    </ul>
</li>
</ol>

<h3>Due Date Status</h3>
<ul>
<li><strong>Pending</strong> - Not yet submitted</li>
<li><strong>Completed</strong> - Report submitted</li>
<li><strong>Overdue</strong> - Past due date, not submitted</li>
<li><strong>Cancelled</strong> - No longer required</li>
</ul>

<h3>Email Reminders</h3>
<p>The system sends automatic email reminders at configurable intervals. Default intervals:</p>
<ul>
<li>30 days before</li>
<li>15 days before</li>
<li>7 days before</li>
<li>3 days before</li>
<li>1 day before</li>
<li>Day of (0 days)</li>
</ul>

<h3>Reminder Recipients</h3>
<p>Add recipients to due dates:</p>
<ul>
<li><strong>Individual Users</strong> - Select from user list</li>
<li><strong>Distribution Lists</strong> - Email groups for multiple recipients</li>
</ul>

<h3>Email Urgency</h3>
<p>Email subject lines indicate urgency:</p>
<ul>
<li><strong>[OVERDUE]</strong> - Past due date</li>
<li><strong>[HIGH PRIORITY]</strong> - 1-3 days remaining</li>
<li><strong>[MEDIUM PRIORITY]</strong> - 4-7 days remaining</li>
<li>Normal - 8+ days remaining</li>
</ul>

<h3>Reminder Schedule</h3>
<p>Reminders are sent daily at 8:00 AM Eastern time.</p>',

            'notification-recipients' => '<h2>Notification Recipients</h2>
<p>Configure who receives contract report reminders.</p>

<h3>Accessing Recipient Management</h3>
<p>Navigate to <strong>Contracts > Notification Recipients</strong> in the sidebar.</p>

<h3>Two Tab Interface</h3>

<h4>Users Tab</h4>
<p>Shows all active users with:</p>
<ul>
<li>Name and email</li>
<li>Whether they\'re in the notification system</li>
<li>Option to add/remove from notifications</li>
</ul>

<h4>Distribution Lists Tab</h4>
<p>Create email groups for multiple recipients:</p>
<ol>
<li>Click <strong>New Distribution List</strong></li>
<li>Enter:
    <ul>
    <li><strong>Name</strong> - List name</li>
    <li><strong>Email</strong> - Group email address</li>
    <li><strong>Description</strong> - What this list is for</li>
    </ul>
</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Approved Domains</h3>
<p>Distribution list emails are validated against approved domains to prevent spam. Contact your administrator if you need to add a new domain.</p>

<h3>Assigning Recipients to Due Dates</h3>
<ol>
<li>Open a contract</li>
<li>Find the due date</li>
<li>Click <strong>Manage Recipients</strong></li>
<li>Select users and/or distribution lists</li>
<li>Save</li>
</ol>

<h3>Required Permissions</h3>
<ul>
<li><code>contracts.manage_notifications</code> - Manage notification recipients</li>
</ul>',

            'funder-management' => '<h2>Funder Management</h2>
<p>Track funding organizations and their contact information.</p>

<h3>Accessing Funder Management</h3>
<p>Navigate to <strong>Administration > Funder Management</strong> or access from the Admin Panel.</p>

<h3>Creating a Funder</h3>
<ol>
<li>Click <strong>New Funder</strong></li>
<li>Fill in:
    <ul>
    <li><strong>Name</strong> - Organization name (required)</li>
    <li><strong>Code</strong> - Short code (optional, must be unique)</li>
    <li><strong>Description</strong> - About the funder</li>
    <li><strong>Contact Person</strong> - Primary contact name</li>
    <li><strong>Contact Email</strong> - Email address</li>
    <li><strong>Contact Phone</strong> - Phone number</li>
    <li><strong>Address</strong> - Mailing address</li>
    <li><strong>Website</strong> - Organization website URL</li>
    </ul>
</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Funder Display</h3>
<p>Funders are displayed as "Name (Code)" when linked to contracts.</p>

<h3>Active/Inactive Status</h3>
<p>Deactivate funders no longer in use. Inactive funders are hidden from contract dropdowns but historical data is preserved.</p>

<h3>Deleting Funders</h3>
<p>You cannot delete a funder with associated contracts. Archive contracts first or assign them to a different funder.</p>

<h3>Required Permissions</h3>
<ul>
<li><code>funders.view</code> - View funders</li>
<li><code>funders.create</code> - Create funders</li>
<li><code>funders.edit</code> - Edit funders</li>
<li><code>funders.delete</code> - Delete funders</li>
</ul>',

            // Housing & Projects
            'housing-projects-overview' => '<h2>Housing & Projects Overview</h2>
<p>The Housing & Projects module provides comprehensive project management for housing rehabilitation and community development work.</p>

<h3>Key Components</h3>
<ul>
<li><strong>Projects</strong> - Housing jobs with milestones and tasks</li>
<li><strong>Properties</strong> - Physical property records</li>
<li><strong>Contractors</strong> - Vendor management</li>
<li><strong>Communications Log</strong> - Contact tracking</li>
<li><strong>PDF Form Templates</strong> - Document generation</li>
</ul>

<h3>Project Workflow</h3>
<pre>
1. Property Assessment → Create property record
2. Participant Intake → Link to household
3. Project Creation → Create housing job
4. Contractor Assignment → Assign vendor
5. Milestone Planning → Define project phases
6. Execution → Complete tasks and documents
7. Completion → Close out project
</pre>

<h3>Housing Module Activation</h3>
<p>The housing module must be enabled on a per-program basis. Ask your administrator to enable "Housing Module" on the program settings.</p>

<h3>Access Requirements</h3>
<p>You need appropriate permissions to access housing features:</p>
<ul>
<li><code>properties.view</code> - View properties</li>
<li><code>contractors.view</code> - View contractors</li>
<li><code>housing_jobs.view</code> - View housing jobs</li>
<li><code>projects.view</code> - View projects</li>
<li><code>communications_log.view</code> - View communications</li>
</ul>',

            'property-management' => '<h2>Property Management</h2>
<p>Track properties associated with housing rehabilitation projects.</p>

<h3>Accessing Property Management</h3>
<p>Navigate to <strong>Housing & Projects > Properties</strong> in the sidebar.</p>

<h3>Creating a Property</h3>
<ol>
<li>Click <strong>New Property</strong></li>
<li>Fill in property details:
    <ul>
    <li><strong>Address</strong> - Full street address</li>
    <li><strong>City, State, ZIP, County</strong></li>
    <li><strong>Property Type</strong> - Residential, Commercial, etc.</li>
    <li><strong>Year Built</strong></li>
    <li><strong>Bedrooms/Bathrooms</strong></li>
    <li><strong>Square Footage</strong></li>
    <li><strong>Status</strong> - Active, Inactive, Sold, Demolished</li>
    </ul>
</li>
<li>Link to a <strong>Household</strong> (required)</li>
<li>Optionally link to a specific <strong>Participant</strong></li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Property Relationships</h3>
<ul>
<li><strong>Household</strong> - Required, links to family unit</li>
<li><strong>Participant</strong> - Optional, specific owner/tenant</li>
<li><strong>Housing Jobs</strong> - Projects at this property</li>
</ul>

<h3>Property Notes</h3>
<p>Add notes to track property history, inspection results, or other details. Notes are timestamped with the author.</p>

<h3>Property Documents</h3>
<p>Attach documents like:</p>
<ul>
<li>Title documents</li>
<li>Insurance certificates</li>
<li>Photos</li>
<li>Inspection reports</li>
</ul>

<h3>Searching Properties</h3>
<p>Search by:</p>
<ul>
<li>Address</li>
<li>City or ZIP code</li>
<li>Participant name</li>
<li>Household code</li>
</ul>

<h3>Filtering</h3>
<ul>
<li>By property status</li>
<li>By property type</li>
<li>By program</li>
</ul>',

            'contractor-management' => '<h2>Contractor Management</h2>
<p>Track contractors, their credentials, and compliance status.</p>

<h3>Accessing Contractor Management</h3>
<p>Navigate to <strong>Housing & Projects > Contractors</strong> in the sidebar.</p>

<h3>Creating a Contractor</h3>
<ol>
<li>Click <strong>New Contractor</strong></li>
<li>Fill in company information:
    <ul>
    <li><strong>Company Name</strong> (required)</li>
    <li><strong>Address</strong></li>
    <li><strong>Primary Contact</strong> - Name, email, phone</li>
    <li><strong>EIN</strong> - Employer Identification Number</li>
    </ul>
</li>
<li>Add licensing information:
    <ul>
    <li><strong>License Number</strong></li>
    <li><strong>License Expiration</strong></li>
    </ul>
</li>
<li>Add insurance information:
    <ul>
    <li><strong>Insurance Provider</strong></li>
    <li><strong>Policy Number</strong></li>
    <li><strong>Insurance Expiration</strong></li>
    <li><strong>Liability Coverage Amount</strong></li>
    </ul>
</li>
<li>Select <strong>Services Offered</strong></li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Contractor Status</h3>
<ul>
<li><strong>Active</strong> - Available for new projects</li>
<li><strong>Inactive</strong> - Not currently taking work</li>
<li><strong>Suspended</strong> - Temporarily restricted</li>
</ul>

<h3>Compliance Tracking</h3>
<p>The system automatically flags:</p>
<ul>
<li><span style="color: red;">Expired License</span> - License expiration has passed</li>
<li><span style="color: red;">Expired Insurance</span> - Insurance expiration has passed</li>
</ul>

<h3>Filtering Contractors</h3>
<ul>
<li>By status (Active, Inactive, Suspended)</li>
<li>By expired license</li>
<li>By expired insurance</li>
</ul>

<h3>Contractor Documents</h3>
<p>Attach and track:</p>
<ul>
<li>License copies</li>
<li>Insurance certificates</li>
<li>W-9 forms</li>
<li>Other certifications</li>
</ul>',

            'housing-jobs-projects' => '<h2>Housing Jobs & Projects</h2>
<p>Housing jobs (projects) track the full lifecycle of housing rehabilitation work.</p>

<h3>Job Number Format</h3>
<p>Jobs are assigned auto-generated numbers: <code>JOB-YYYY-NNNNN</code></p>
<p>Example: JOB-2025-00042</p>

<h3>Creating a Housing Job</h3>
<ol>
<li>Go to <strong>Projects</strong></li>
<li>Click <strong>New Project</strong></li>
<li>Fill in:
    <ul>
    <li><strong>Title</strong> - Project name</li>
    <li><strong>Participant</strong> - Must be head of household</li>
    <li><strong>Property</strong> - Select or create property</li>
    <li><strong>Contractor</strong> (optional)</li>
    <li><strong>Program</strong> - Must have housing module enabled</li>
    <li><strong>Service</strong> (optional)</li>
    <li><strong>Priority</strong> - Low, Medium, High, Emergency</li>
    </ul>
</li>
<li>Set dates:
    <ul>
    <li><strong>Request Date</strong></li>
    <li><strong>Target Completion</strong></li>
    </ul>
</li>
<li>Set budget:
    <ul>
    <li><strong>Estimated Cost</strong></li>
    <li><strong>Approved Amount</strong></li>
    </ul>
</li>
</ol>

<h3>Job Status Flow</h3>
<pre>
Pending → Approved → In Progress → Completed
                   → On Hold
                   → Cancelled
</pre>

<h3>Job Priority Colors</h3>
<ul>
<li><span style="background: gray; color: white; padding: 2px 6px;">Low</span></li>
<li><span style="background: blue; color: white; padding: 2px 6px;">Medium</span></li>
<li><span style="background: orange; color: white; padding: 2px 6px;">High</span></li>
<li><span style="background: red; color: white; padding: 2px 6px;">Emergency</span></li>
</ul>

<h3>Quick Create Features</h3>
<ul>
<li><strong>Quick Participant</strong> - Create participant + household from project form</li>
<li><strong>Quick Property</strong> - Create property from household address</li>
</ul>

<h3>Project Templates</h3>
<p>Save completed projects as templates to quickly create similar projects with predefined milestones and documents.</p>',

            'project-milestones-tasks' => '<h2>Milestones, Tasks & Documents</h2>
<p>Break projects into manageable phases with tasks and document requirements.</p>

<h3>Milestones</h3>
<p>Milestones represent major project phases:</p>
<ul>
<li>Executed in sequence order</li>
<li>Status: Not Started → In Progress → Completed</li>
<li>First milestone starts automatically</li>
<li>Next milestone starts when current completes</li>
</ul>

<h3>Creating Milestones</h3>
<ol>
<li>Open a housing job</li>
<li>Go to <strong>Milestones</strong> section</li>
<li>Click <strong>Add Milestone</strong></li>
<li>Enter:
    <ul>
    <li><strong>Name</strong></li>
    <li><strong>Sequence Order</strong></li>
    <li><strong>Target Date</strong></li>
    <li><strong>Color</strong> (for visual identification)</li>
    </ul>
</li>
</ol>

<h3>Tasks</h3>
<p>Add tasks to milestones:</p>
<ul>
<li><strong>Task Name</strong> and description</li>
<li><strong>Assigned To</strong> - User responsible</li>
<li><strong>Deadline</strong></li>
<li><strong>Requires Approval</strong> - Flag if task needs sign-off</li>
</ul>

<h3>Task Status</h3>
<ul>
<li><span style="color: gray;">Pending</span> - Not started</li>
<li><span style="color: blue;">In Progress</span> - Work underway</li>
<li><span style="color: green;">Completed</span> - Done</li>
<li><span style="color: red;">Overdue</span> - Past deadline</li>
</ul>

<h3>Document Requirements</h3>
<p>Define required documents for each milestone:</p>
<ul>
<li><strong>Document Type</strong> - What\'s needed</li>
<li><strong>Description</strong></li>
<li><strong>Assigned To</strong> - Who should upload</li>
<li><strong>Requires Approval</strong> - Needs sign-off</li>
<li><strong>PDF Template</strong> - Auto-generate from template</li>
</ul>

<h3>Document Status</h3>
<ul>
<li><span style="color: gray;">Not Uploaded</span></li>
<li><span style="color: blue;">Uploaded</span> - No approval needed</li>
<li><span style="color: orange;">Pending Review</span> - Awaiting approval</li>
<li><span style="color: green;">Approved</span></li>
<li><span style="color: red;">Rejected</span></li>
</ul>

<h3>Milestone Completion</h3>
<p>A milestone can be completed when:</p>
<ul>
<li>All tasks are completed</li>
<li>All required documents are uploaded/approved</li>
<li>No rejected documents exist</li>
</ul>',

            'communications-log' => '<h2>Communications Log</h2>
<p>Document all communications with participants, contractors, and other parties.</p>

<h3>Accessing Communications Log</h3>
<p>Navigate to <strong>Housing & Projects > Communications Log</strong> in the sidebar.</p>

<h3>Logging a Communication</h3>
<ol>
<li>Click <strong>New Communication</strong></li>
<li>Select:
    <ul>
    <li><strong>Type</strong> - Phone, Email, In Person, Site Visit, Video Call, Text, Mail, Voicemail, Fax</li>
    <li><strong>Direction</strong> - Incoming or Outgoing</li>
    <li><strong>Category</strong> - General, Intake, Follow-up, Complaint, Inquiry, Referral, etc.</li>
    <li><strong>Priority</strong> - Low, Normal, High, Urgent</li>
    </ul>
</li>
<li>Enter details:
    <ul>
    <li><strong>Contact Name</strong></li>
    <li><strong>Contact Role</strong> - Homeowner, Contractor, Inspector, etc.</li>
    <li><strong>Organization</strong></li>
    <li><strong>Phone/Email</strong></li>
    </ul>
</li>
<li>Fill in:
    <ul>
    <li><strong>Subject</strong></li>
    <li><strong>Summary</strong></li>
    <li><strong>Outcome</strong></li>
    </ul>
</li>
<li>Link to:
    <ul>
    <li>Participant</li>
    <li>Household</li>
    <li>Property</li>
    <li>Contractor</li>
    </ul>
</li>
</ol>

<h3>Follow-Up Tracking</h3>
<p>Set follow-up reminders:</p>
<ul>
<li><strong>Follow-Up Date</strong> - When to follow up</li>
<li><strong>Follow-Up Action</strong> - What needs to happen</li>
<li><strong>Mark Complete</strong> - When done</li>
</ul>

<h3>Dashboard Statistics</h3>
<p>The communications log shows:</p>
<ul>
<li>Pending follow-ups count</li>
<li>Overdue follow-ups count</li>
<li>Today\'s communications count</li>
</ul>

<h3>Filtering Communications</h3>
<ul>
<li>By type, category, or priority</li>
<li>By direction</li>
<li>By date range</li>
<li>By linked entity</li>
</ul>',

            // Approvals
            'approvals-overview' => '<h2>Approvals Overview</h2>
<p>The Approvals module provides a workflow for reviewing and approving documents and tasks that require sign-off.</p>

<h3>What Requires Approval?</h3>
<ul>
<li><strong>Documents</strong>
    <ul>
    <li>Property documents</li>
    <li>Contractor documents (licenses, insurance)</li>
    <li>Job document checklists</li>
    </ul>
</li>
<li><strong>Tasks</strong>
    <ul>
    <li>Work plan goals (when flagged for approval)</li>
    <li>Milestone tasks (when flagged for approval)</li>
    </ul>
</li>
</ul>

<h3>Approval Workflow</h3>
<ol>
<li><strong>Submission</strong> - Item is submitted with approval flag</li>
<li><strong>Pending</strong> - Appears in approval dashboard</li>
<li><strong>Review</strong> - Approver reviews the item</li>
<li><strong>Decision</strong> - Approve or Reject</li>
<li><strong>Notification</strong> - Submitter notified of decision</li>
</ol>

<h3>Accessing Approvals</h3>
<p>Navigate to <strong>Approvals > Pending Approvals</strong> in the sidebar.</p>
<p>A badge shows the count of pending items.</p>

<h3>Approval Status</h3>
<ul>
<li><span style="background: yellow; padding: 2px 6px;">Pending</span> - Awaiting review</li>
<li><span style="background: green; color: white; padding: 2px 6px;">Approved</span> - Accepted</li>
<li><span style="background: red; color: white; padding: 2px 6px;">Rejected</span> - Declined with reason</li>
</ul>

<h3>Required Permissions</h3>
<ul>
<li><code>approvals.view_dashboard</code> - Access approval dashboard</li>
<li><code>approvals.approve_reject</code> - Approve or reject items</li>
<li><code>approvals.bulk_approve</code> - Bulk approve multiple items</li>
</ul>',

            'approving-rejecting-items' => '<h2>Approving and Rejecting Items</h2>

<h3>Viewing Pending Items</h3>
<p>The Pending Approvals dashboard shows all items awaiting your review:</p>
<ul>
<li>Item type and name</li>
<li>Description preview</li>
<li>Submitter name and email</li>
<li>Submission date</li>
<li>Program association</li>
<li>Current status</li>
</ul>

<h3>Filtering Pending Items</h3>
<ul>
<li><strong>Type</strong> - All, Documents, Tasks</li>
<li><strong>Status</strong> - Pending, Approved, Rejected, All</li>
<li><strong>Program</strong> - Filter by program</li>
<li><strong>Search</strong> - By submitter, program, or item name</li>
</ul>

<h3>Approving an Item</h3>
<ol>
<li>Find the item in the list</li>
<li>Click the green checkmark button</li>
<li>Item is immediately approved</li>
</ol>

<h3>Rejecting an Item</h3>
<ol>
<li>Find the item in the list</li>
<li>Click the red X button</li>
<li>Enter rejection reason (minimum 10 characters)</li>
<li>Click <strong>Reject</strong></li>
</ol>

<h3>Bulk Approval</h3>
<ol>
<li>Select multiple items using checkboxes</li>
<li>Click <strong>Approve All Selected</strong></li>
<li>All selected pending items are approved</li>
</ol>

<h3>Email Notifications</h3>
<p>Submitters receive email notifications when their items are:</p>
<ul>
<li>Approved - Confirmation with details</li>
<li>Rejected - Rejection reason and resubmit instructions</li>
</ul>

<h3>Approval History</h3>
<p>All approval actions are logged with:</p>
<ul>
<li>Action taken (approved/rejected)</li>
<li>User who took action</li>
<li>Timestamp</li>
<li>Rejection reason (if applicable)</li>
</ul>',

            // Reports
            'csbg-reports' => '<h2>CSBG Reports</h2>
<p>Generate reports compliant with Community Services Block Grant Module 4 Section C requirements.</p>

<h3>Accessing Reports</h3>
<p>Navigate to <strong>Reports > Reporting Dashboard</strong> in the sidebar.</p>

<h3>Report Parameters</h3>
<ul>
<li><strong>Date Range</strong> - Select start and end dates</li>
<li><strong>Program</strong> - Filter by specific program or all</li>
<li><strong>Reference Data Set</strong> - Version of data definitions</li>
<li><strong>Heads of Household Only</strong> - Count only household heads to avoid double-counting families</li>
</ul>

<h3>Advanced Filters</h3>
<p>Optionally filter by:</p>
<ul>
<li>Gender</li>
<li>Ethnicity</li>
<li>Age range</li>
<li>Education level</li>
<li>Work status</li>
<li>Household type</li>
</ul>

<h3>Report Sections</h3>
<ol>
<li><strong>Demographics</strong> - Gender, ethnicity, race, education</li>
<li><strong>Characteristics</strong> - Military status, disability, age groups</li>
<li><strong>Employment & Income</strong> - Work status, income sources, poverty levels</li>
<li><strong>Health & Housing</strong> - Insurance, housing status, household type</li>
</ol>

<h3>Drill-Down</h3>
<p>Click any data point to see the individual participants counted in that category.</p>

<h3>Export Formats</h3>
<ul>
<li><strong>PDF</strong> - Professional formatted report for printing</li>
<li><strong>CSV</strong> - Raw data for spreadsheet analysis</li>
<li><strong>Excel</strong> - Formatted spreadsheet with colors and headers</li>
</ul>

<h3>Visualizations</h3>
<p>Interactive charts available:</p>
<ul>
<li>Gender Distribution (Pie)</li>
<li>Age Groups (Bar)</li>
<li>Education Level (Doughnut)</li>
<li>Poverty Levels (Bar)</li>
<li>Work Status (Pie)</li>
<li>Housing Status (Doughnut)</li>
</ul>',

            // Accounting & Fiscal
            'donorperfect-gifts' => '<h2>DonorPerfect Gifts</h2>
<p>View and manage gift data synchronized from DonorPerfect donor management system.</p>

<h3>Accessing DonorPerfect Gifts</h3>
<p>Navigate to <strong>Accounting & Fiscal > DonorPerfect Gifts</strong> in the sidebar.</p>

<h3>Gifts List</h3>
<p>View all gifts with:</p>
<ul>
<li>Donor information</li>
<li>Gift amount and date</li>
<li>Gift type and payment type</li>
<li>GL code and campaign</li>
<li>Sync status to Sage Intacct</li>
</ul>

<h3>Filtering Gifts</h3>
<ul>
<li><strong>Date range</strong> - Start and end dates</li>
<li><strong>Gift type</strong> - Type of gift</li>
<li><strong>Payment type</strong> - Cash, Check, Credit Card, etc.</li>
<li><strong>GL code</strong> - General Ledger code</li>
<li><strong>Campaign</strong></li>
<li><strong>Sync status</strong> - All, Success, Failed, Pending, Not Synced</li>
</ul>

<h3>Selecting Gifts</h3>
<p>Use checkboxes to select gifts for:</p>
<ul>
<li>Batch sync to Sage Intacct</li>
<li>Export to CSV/Excel/PDF</li>
<li>Check image export (ZIP)</li>
</ul>

<h3>Export Options</h3>
<ul>
<li><strong>CSV</strong> - Comma-separated values</li>
<li><strong>Excel</strong> - Formatted spreadsheet</li>
<li><strong>PDF</strong> - Printable report</li>
<li><strong>Check Images ZIP</strong> - Download check images (password-protected optional)</li>
</ul>

<h3>Tab Interface</h3>
<ol>
<li><strong>Gifts</strong> - Main gift list and actions</li>
<li><strong>Field Mappings</strong> - Map DonorPerfect fields to Sage Intacct</li>
<li><strong>Code Mappings</strong> - Map codes between systems</li>
<li><strong>Conditional Mappings</strong> - Location-based code routing</li>
<li><strong>Sync Filters</strong> - Include/exclude rules for sync</li>
<li><strong>Sync History</strong> - Audit trail of sync operations</li>
</ol>',

            'donorperfect-configuration' => '<h2>DonorPerfect Configuration</h2>
<p>Configure the connection to DonorPerfect API.</p>

<h3>Accessing Configuration</h3>
<p>Navigate to <strong>Accounting & Fiscal > DP Configuration</strong> in the sidebar.</p>

<h3>API Settings</h3>
<ul>
<li><strong>API URL</strong> - DonorPerfect API endpoint</li>
<li><strong>API Key</strong> - Your API key (encrypted)</li>
<li><strong>API Login</strong> - Login credential (encrypted)</li>
<li><strong>API Password</strong> - Password (encrypted)</li>
<li><strong>Cache Duration</strong> - How long to cache data (seconds)</li>
</ul>

<h3>Testing Connection</h3>
<p>Click <strong>Test Connection</strong> to verify API credentials are working.</p>

<h3>Field Selection</h3>
<p>Choose which fields to retrieve from DonorPerfect:</p>
<ul>
<li><strong>Donor fields</strong> - ID, name, contact info</li>
<li><strong>Gift fields</strong> - Amount, date, type, codes</li>
<li><strong>User-defined fields</strong> - Custom fields from your DonorPerfect setup</li>
</ul>

<h3>Field Discovery</h3>
<p>The system can automatically discover available fields from the DonorPerfect database. Click <strong>Refresh Fields</strong> to update the available field list.</p>

<h3>Security Note</h3>
<p>All API credentials are encrypted at rest and never displayed in plain text after being saved.</p>

<h3>Required Permissions</h3>
<ul>
<li><code>donor_gifts.configure</code> - Manage configuration</li>
</ul>',

            'sage-intacct-sync' => '<h2>Sage Intacct Sync</h2>
<p>Synchronize gift data from DonorPerfect to Sage Intacct accounting system.</p>

<h3>Accessing Sage Intacct</h3>
<p>Navigate to <strong>Accounting & Fiscal > Sage Intacct Sync</strong> in the sidebar.</p>

<h3>Authentication Methods</h3>
<p>Two authentication options are supported:</p>

<h4>1. XML Web Services (Traditional)</h4>
<ul>
<li>Session-based authentication</li>
<li>Requires: Sender ID, Sender Password, Web Services User, Web Services Password</li>
<li>Best for firewall/on-premise environments</li>
</ul>

<h4>2. OAuth 2.0 (Modern)</h4>
<ul>
<li>Token-based authentication</li>
<li>Requires: Company ID, Client ID, Client Secret</li>
<li>Per-user authorization required</li>
</ul>

<h3>Configuration Settings</h3>
<ul>
<li><strong>Company ID</strong> - Sage Intacct company</li>
<li><strong>Location ID</strong> - Default location</li>
<li><strong>Department ID</strong> - Default department</li>
<li><strong>Class ID</strong> - Default class</li>
<li><strong>Custom Object</strong> - Where to store synced data</li>
</ul>

<h3>Code Mappings</h3>
<p>Map DonorPerfect codes to Sage Intacct:</p>
<ul>
<li><strong>GL Codes</strong> - General Ledger mappings</li>
<li><strong>Campaigns</strong> - Campaign to location mappings</li>
<li><strong>Solicit Codes</strong> - Solicitation code mappings</li>
<li><strong>Payment Methods</strong> - Payment type mappings</li>
</ul>

<h3>Sync Filters</h3>
<p>Create rules to include or exclude gifts:</p>
<ul>
<li>Filter by field values</li>
<li>Operators: equals, contains, greater than, etc.</li>
<li>Priority-based execution</li>
</ul>

<h3>Running a Sync</h3>
<ol>
<li>Select gifts from the Gifts tab</li>
<li>Click <strong>Sync to Sage Intacct</strong></li>
<li>Review results in Sync History</li>
</ol>

<h3>Sync Status</h3>
<ul>
<li><span style="color: green;">Success</span> - Synced successfully</li>
<li><span style="color: red;">Failed</span> - Error occurred (see details)</li>
<li><span style="color: orange;">Pending</span> - In queue</li>
<li><span style="color: gray;">Not Synced</span> - Never synced</li>
</ul>',

            'check-images' => '<h2>Check Images</h2>
<p>View check scan images associated with DonorPerfect gifts.</p>

<h3>Viewing Check Images</h3>
<p>From the DonorPerfect Gifts list:</p>
<ol>
<li>Find a gift with a check image</li>
<li>Click the check image icon/link</li>
<li>Image opens in a viewer</li>
</ol>

<h3>Image Formats</h3>
<p>The system supports:</p>
<ul>
<li>TIFF (multi-page support)</li>
<li>PNG</li>
<li>JPEG</li>
</ul>

<h3>TIFF Conversion</h3>
<p>Multi-page TIFF files are automatically converted to PNG for web viewing. Each page is displayed separately.</p>

<h3>Exporting Check Images</h3>
<ol>
<li>Select gifts with check images</li>
<li>Choose <strong>Export Check Images</strong></li>
<li>Optional: Set password protection</li>
<li>Download ZIP file</li>
</ol>

<h3>Security Features</h3>
<ul>
<li>Images are encrypted at rest</li>
<li>Access is logged for audit purposes</li>
<li>Images are cached for performance</li>
</ul>

<h3>Audit Log</h3>
<p>Administrators can view the check image audit log showing:</p>
<ul>
<li>Who accessed each image</li>
<li>When it was accessed</li>
<li>IP address</li>
<li>Action taken (viewed, downloaded, etc.)</li>
</ul>',

            // Administration
            'admin-panel-overview' => '<h2>Admin Panel Overview</h2>
<p>The Admin Panel provides centralized access to all administrative functions.</p>

<h3>Accessing the Admin Panel</h3>
<p>Navigate to <strong>Administration > Admin Panel</strong> in the sidebar.</p>

<h3>Tab Overview</h3>
<ul>
<li><strong>Overview</strong> - System statistics and quick metrics</li>
<li><strong>Programs</strong> - Create and manage programs</li>
<li><strong>Users</strong> - Quick user management</li>
<li><strong>Data Management</strong> - Bulk data operations</li>
<li><strong>Site Settings</strong> - Logo, name, and branding</li>
<li><strong>Contract Settings</strong> - Email templates and reminders</li>
<li><strong>Roles & Permissions</strong> - Access control</li>
<li><strong>Reference Data</strong> - Dropdown values and versioning</li>
</ul>

<h3>System Statistics</h3>
<p>The Overview tab shows:</p>
<ul>
<li>Total participants count</li>
<li>Total households count</li>
<li>Active programs count</li>
<li>Active users count</li>
<li>Active contracts count</li>
</ul>

<h3>Quick Actions</h3>
<p>From the Admin Panel you can quickly:</p>
<ul>
<li>Create new programs</li>
<li>Create new users</li>
<li>Update site settings</li>
<li>Manage permissions</li>
</ul>

<h3>Required Permissions</h3>
<p>The Admin Panel requires <code>admin.access</code> permission or Administrator role.</p>',

            'user-management' => '<h2>User Management</h2>
<p>Manage user accounts, roles, and program assignments.</p>

<h3>Accessing User Management</h3>
<p>Go to <strong>Administration > Admin Panel > Users</strong> or the dedicated User Management page.</p>

<h3>Creating a User</h3>
<ol>
<li>Click <strong>New User</strong></li>
<li>Enter:
    <ul>
    <li><strong>Name</strong> - Full name</li>
    <li><strong>Email</strong> - Must be unique</li>
    <li><strong>Password</strong> - Minimum 8 characters</li>
    <li><strong>Confirm Password</strong></li>
    </ul>
</li>
<li>Assign <strong>Roles</strong> (one or more)</li>
<li>Assign <strong>Programs</strong> (determines data access)</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Editing Users</h3>
<ul>
<li><strong>Update Name/Email</strong> - Edit basic information</li>
<li><strong>Change Roles</strong> - Add or remove roles</li>
<li><strong>Change Programs</strong> - Update program assignments</li>
<li><strong>Set Password</strong> - Admin password reset</li>
<li><strong>Send Password Reset</strong> - Email reset link</li>
</ul>

<h3>User Status</h3>
<ul>
<li><strong>Active</strong> - Can log in</li>
<li><strong>Inactive</strong> - Cannot log in</li>
</ul>
<p>Toggle status with the activate/deactivate button.</p>

<h3>Bulk Operations</h3>
<p>Select multiple users to:</p>
<ul>
<li><strong>Bulk Assign Roles</strong> - Add or replace roles</li>
<li><strong>Bulk Assign Programs</strong> - Add or replace programs</li>
<li><strong>Bulk Delete</strong> - Remove multiple users</li>
</ul>

<h3>Assignment Modes</h3>
<ul>
<li><strong>ADD</strong> - Merge with existing assignments</li>
<li><strong>REPLACE</strong> - Overwrite existing assignments</li>
</ul>

<h3>Self-Protection</h3>
<p>You cannot:</p>
<ul>
<li>Deactivate your own account</li>
<li>Delete your own account</li>
</ul>

<h3>Required Permissions</h3>
<ul>
<li><code>users.view</code> - View user list</li>
<li><code>users.edit</code> - Edit users</li>
<li><code>users.delete</code> - Delete users</li>
</ul>',

            'roles-permissions' => '<h2>Roles & Permissions</h2>
<p>CAP TRACK uses role-based access control (RBAC) to manage what users can see and do.</p>

<h3>Understanding Roles vs Permissions</h3>
<ul>
<li><strong>Permissions</strong> - Specific actions (e.g., "create participant")</li>
<li><strong>Roles</strong> - Collections of permissions (e.g., "Case Manager")</li>
</ul>

<h3>Accessing Role Management</h3>
<p>Go to <strong>Administration > Admin Panel > Roles & Permissions</strong></p>

<h3>Creating a Role</h3>
<ol>
<li>Click <strong>New Role</strong></li>
<li>Enter:
    <ul>
    <li><strong>Name</strong> - Internal identifier (unique)</li>
    <li><strong>Display Name</strong> - User-friendly name</li>
    <li><strong>Description</strong> - What this role is for</li>
    </ul>
</li>
<li>Select permissions to assign</li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Permission Categories</h3>
<table>
<tr><th>Category</th><th>Examples</th></tr>
<tr><td>Participants</td><td>view, create, edit, delete, import, export</td></tr>
<tr><td>Households</td><td>view, edit</td></tr>
<tr><td>Programs</td><td>view, create, edit, delete</td></tr>
<tr><td>Services</td><td>view, create, edit, delete</td></tr>
<tr><td>Contracts</td><td>view, create, edit, delete, manage</td></tr>
<tr><td>Properties</td><td>view, create, update, delete</td></tr>
<tr><td>Contractors</td><td>view, create, update, delete</td></tr>
<tr><td>Reports</td><td>csbg</td></tr>
<tr><td>Admin</td><td>access, users, roles</td></tr>
</table>

<h3>Permission Naming</h3>
<p>Permissions follow the pattern: <code>resource.action</code></p>
<p>Example: <code>participants.create</code></p>

<h3>Deleting Roles/Permissions</h3>
<ul>
<li>Cannot delete a role with assigned users</li>
<li>Cannot delete a permission assigned to roles</li>
</ul>

<h3>Administrator Role</h3>
<p>The Administrator role has full system access and bypasses most permission checks.</p>',

            'reference-data' => '<h2>Reference Data Management</h2>
<p>Reference data controls the dropdown values used throughout CAP TRACK, ensuring CSBG compliance.</p>

<h3>What is Reference Data?</h3>
<p>Reference data includes:</p>
<ul>
<li>Gender options</li>
<li>Education levels</li>
<li>Ethnicity categories</li>
<li>Race options</li>
<li>Military status options</li>
<li>Work status options</li>
<li>Income sources</li>
<li>Non-cash benefits</li>
<li>Health insurance options</li>
<li>Housing status options</li>
<li>Household types</li>
</ul>

<h3>Data Set Versioning</h3>
<p>Reference data is versioned to support:</p>
<ul>
<li>Historical accuracy (what options were available when data was entered)</li>
<li>CSBG compliance updates</li>
<li>Controlled rollout of changes</li>
</ul>

<h3>Version States</h3>
<ul>
<li><strong>Active</strong> - Current production data set</li>
<li><strong>Draft</strong> - Editable copy being prepared</li>
<li><strong>Published</strong> - Finalized historical versions</li>
</ul>

<h3>Creating a New Version</h3>
<ol>
<li>Click <strong>Create Editable Version</strong></li>
<li>System copies active set with new version number</li>
<li>Edit items in the draft</li>
<li>When ready, publish and activate</li>
</ol>

<h3>Editing Reference Items</h3>
<p>In a draft data set, you can:</p>
<ul>
<li>Add new values</li>
<li>Edit labels and descriptions</li>
<li>Change sort order</li>
<li>Deactivate options</li>
</ul>

<h3>Import Default</h3>
<p>Designate which data set to use for data imports. This ensures imported data uses the correct reference values.</p>

<h3>Required Permissions</h3>
<ul>
<li><code>reference_data.view</code> - View reference data</li>
<li><code>reference_data.create</code> - Create items/versions</li>
<li><code>reference_data.edit</code> - Edit items</li>
<li><code>reference_data.delete</code> - Delete items</li>
</ul>',

            'login-audit-log' => '<h2>Login Audit Log</h2>
<p>Track all authentication events for security and compliance.</p>

<h3>Accessing the Audit Log</h3>
<p>Navigate to <strong>Administration > Login Audit Log</strong> in the sidebar.</p>

<h3>Events Tracked</h3>
<ul>
<li><strong>Login Success</strong> - Successful user login</li>
<li><strong>Login Failed</strong> - Failed login attempt</li>
<li><strong>Logout</strong> - User logout</li>
<li><strong>Password Reset Requested</strong></li>
<li><strong>Password Reset Completed</strong></li>
</ul>

<h3>Information Captured</h3>
<p>For each event:</p>
<ul>
<li>User name and email</li>
<li>IP address</li>
<li>Browser type</li>
<li>Operating system</li>
<li>Device type (Desktop, Mobile, Tablet)</li>
<li>Timestamp</li>
<li>Failure reason (for failed logins)</li>
</ul>

<h3>Filtering Events</h3>
<ul>
<li><strong>Search</strong> - By name, email, or IP</li>
<li><strong>Action</strong> - By event type</li>
<li><strong>User</strong> - By specific user</li>
<li><strong>Date range</strong> - From/to dates</li>
</ul>

<h3>Statistics Dashboard</h3>
<p>At-a-glance metrics:</p>
<ul>
<li>Today\'s successful logins</li>
<li>Today\'s failed logins</li>
<li>Current active sessions</li>
<li>Unique users logged in today</li>
</ul>

<h3>Active Sessions</h3>
<p>View currently active sessions with:</p>
<ul>
<li>User name and email</li>
<li>IP address</li>
<li>Browser and platform</li>
<li>Last activity timestamp</li>
</ul>

<h3>Required Permissions</h3>
<ul>
<li><code>audit.login_view</code> - Access login audit log</li>
</ul>',

            'poverty-guidelines' => '<h2>Poverty Guidelines</h2>
<p>Manage HHS Federal Poverty Guidelines for income calculations.</p>

<h3>What are Poverty Guidelines?</h3>
<p>The U.S. Department of Health and Human Services (HHS) publishes annual poverty guidelines used to determine program eligibility.</p>

<h3>Guideline Components</h3>
<ul>
<li><strong>Year</strong> - Guideline year (2020-2030)</li>
<li><strong>Region</strong> - 48 Contiguous States, Alaska, Hawaii</li>
<li><strong>Family Size</strong> - 1-20 persons</li>
<li><strong>Period</strong> - Monthly or Annual</li>
</ul>

<h3>Poverty Thresholds</h3>
<p>Each guideline includes amounts for:</p>
<ul>
<li>50% of poverty</li>
<li>75% of poverty</li>
<li>100% of poverty (baseline)</li>
<li>125% of poverty</li>
<li>150% of poverty</li>
<li>200% of poverty</li>
<li>And more...</li>
</ul>

<h3>How Poverty is Calculated</h3>
<p>The system calculates poverty percentage based on:</p>
<ol>
<li>Household annual income</li>
<li>Number of people in household</li>
<li>Current year\'s guidelines</li>
<li>Region (defaults to 48 states)</li>
</ol>

<h3>Managing Guidelines</h3>
<ol>
<li>Go to <strong>Administration > Poverty Guidelines</strong></li>
<li>Filter by year, region, and period</li>
<li>Add or edit guidelines as needed</li>
</ol>

<h3>Annual Updates</h3>
<p>HHS typically publishes new guidelines in January. Update CAP TRACK with new values each year.</p>

<h3>Required Permissions</h3>
<ul>
<li><code>poverty_guidelines.view</code> - View guidelines</li>
<li><code>poverty_guidelines.create</code> - Create guidelines</li>
<li><code>poverty_guidelines.edit</code> - Edit guidelines</li>
</ul>',

            // User Guide
            'profile-settings' => '<h2>Profile Settings</h2>
<p>Manage your personal information and account preferences.</p>

<h3>Accessing Settings</h3>
<p>Click your name at the bottom of the sidebar and select <strong>Settings</strong>.</p>

<h3>Profile Tab</h3>
<p>Update your basic information:</p>
<ul>
<li><strong>Name</strong> - Your display name</li>
<li><strong>Email</strong> - Your login email address</li>
</ul>

<h3>Changing Your Email</h3>
<p>When you change your email:</p>
<ol>
<li>Enter the new email address</li>
<li>Click <strong>Save</strong></li>
<li>A verification email is sent to the new address</li>
<li>Click the verification link to confirm</li>
</ol>
<p>Until verified, some features may be limited.</p>

<h3>Email Verification</h3>
<p>If your email is unverified, you\'ll see a banner prompting verification. Click <strong>Resend Verification</strong> to receive a new link.</p>',

            'password-management' => '<h2>Password Management</h2>
<p>Keep your account secure by maintaining a strong password.</p>

<h3>Accessing Password Settings</h3>
<ol>
<li>Go to <strong>Settings</strong></li>
<li>Click the <strong>Password</strong> tab</li>
</ol>

<h3>Changing Your Password</h3>
<ol>
<li>Enter your <strong>Current Password</strong></li>
<li>Enter your <strong>New Password</strong></li>
<li>Enter new password again in <strong>Confirm Password</strong></li>
<li>Click <strong>Save</strong></li>
</ol>

<h3>Password Requirements</h3>
<p>Passwords must:</p>
<ul>
<li>Be at least 8 characters long</li>
<li>Match in both new password fields</li>
</ul>

<h3>Forgot Your Password?</h3>
<p>If you can\'t log in:</p>
<ol>
<li>Go to the login page</li>
<li>Click <strong>Forgot Password</strong></li>
<li>Enter your email address</li>
<li>Check your email for a reset link</li>
<li>Create a new password</li>
</ol>

<h3>Security Tips</h3>
<ul>
<li>Use a unique password for CAP TRACK</li>
<li>Don\'t share your password with others</li>
<li>Change your password periodically</li>
<li>Log out when using shared computers</li>
</ul>',

            'appearance-settings' => '<h2>Appearance Settings</h2>
<p>Customize how CAP TRACK looks for you.</p>

<h3>Accessing Appearance Settings</h3>
<ol>
<li>Go to <strong>Settings</strong></li>
<li>Click the <strong>Appearance</strong> tab</li>
</ol>

<h3>Theme Options</h3>
<p>Choose from three themes:</p>

<h4>Light Theme</h4>
<p>Bright background with dark text. Best for well-lit environments.</p>

<h4>Dark Theme</h4>
<p>Dark background with light text. Easier on the eyes in low-light conditions.</p>

<h4>System Theme</h4>
<p>Automatically matches your device\'s theme setting. If your device switches between light and dark mode, CAP TRACK will follow.</p>

<h3>Changing Themes</h3>
<ol>
<li>Click on your preferred theme option</li>
<li>The change applies immediately</li>
<li>Your preference is saved automatically</li>
</ol>

<h3>Benefits of Dark Mode</h3>
<ul>
<li>Reduces eye strain in low light</li>
<li>May save battery on OLED screens</li>
<li>Some users find it more comfortable for extended use</li>
</ul>',

            'using-help-feedback' => '<h2>Using Help & Feedback</h2>
<p>CAP TRACK provides multiple ways to get help and share your feedback.</p>

<h3>Contextual Help (Help Button)</h3>
<p>Look for the floating <strong>?</strong> button in the corner of each page.</p>
<ol>
<li>Click the help button</li>
<li>See documentation relevant to your current page</li>
<li>Click an article to read more</li>
<li>Click <strong>View All Documentation</strong> to browse everything</li>
</ol>

<h3>Full Documentation</h3>
<p>Access complete documentation from the sidebar:</p>
<ol>
<li>Click <strong>Documentation</strong> in the sidebar</li>
<li>Browse categories or search</li>
<li>Bookmark articles for quick access</li>
<li>Rate articles as helpful or not</li>
</ol>

<h3>Providing Feedback</h3>
<p>Help improve CAP TRACK with your feedback:</p>
<ol>
<li>Click the <strong>Feedback</strong> button (usually near the help button)</li>
<li>Describe your suggestion, issue, or question</li>
<li>Add a screenshot if helpful</li>
<li>Submit your feedback</li>
</ol>

<h3>What to Include in Feedback</h3>
<ul>
<li><strong>For bugs</strong> - What you expected vs. what happened</li>
<li><strong>For suggestions</strong> - What problem this would solve</li>
<li><strong>For questions</strong> - What you\'re trying to accomplish</li>
</ul>

<h3>Feedback Status</h3>
<p>If you have access, you can track feedback status:</p>
<ul>
<li><strong>New</strong> - Just submitted</li>
<li><strong>Under Review</strong> - Being evaluated</li>
<li><strong>In Progress</strong> - Being worked on</li>
<li><strong>Resolved</strong> - Completed</li>
</ul>',

            'tips-shortcuts' => '<h2>Tips & Best Practices</h2>
<p>Get the most out of CAP TRACK with these tips.</p>

<h3>Navigation Tips</h3>
<ul>
<li>Use the sidebar to navigate between sections</li>
<li>Breadcrumbs help you know where you are</li>
<li>Use your browser\'s back button to return to previous pages</li>
<li>Bookmark frequently used pages</li>
</ul>

<h3>Search Tips</h3>
<ul>
<li>Search bars filter as you type</li>
<li>Try partial names if you\'re not sure of spelling</li>
<li>Use household codes for quick lookup</li>
<li>Clear filters to see all results</li>
</ul>

<h3>Data Entry Tips</h3>
<ul>
<li>Complete all required fields (marked with *)</li>
<li>Save frequently on long forms</li>
<li>Use tab to move between fields</li>
<li>Double-check dates before saving</li>
</ul>

<h3>Best Practices</h3>
<ul>
<li><strong>Keep data current</strong> - Update participant info regularly</li>
<li><strong>Document communications</strong> - Log calls and emails in the communications log</li>
<li><strong>Use notes</strong> - Add notes for context others might need</li>
<li><strong>Complete enrollments</strong> - Mark services complete when finished</li>
<li><strong>Review before submitting</strong> - Double-check reports before export</li>
</ul>

<h3>Performance Tips</h3>
<ul>
<li>Close unused browser tabs</li>
<li>Refresh if data seems stale</li>
<li>Use filters to reduce large data sets</li>
<li>Export large reports during off-peak hours</li>
</ul>

<h3>Getting Help</h3>
<ul>
<li>Check contextual help first</li>
<li>Browse full documentation</li>
<li>Submit feedback for questions</li>
<li>Contact your administrator for access issues</li>
</ul>',
        ];
    }
}
