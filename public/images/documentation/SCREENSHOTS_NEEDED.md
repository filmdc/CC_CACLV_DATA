# Documentation Screenshots Needed

The following screenshots are referenced in the documentation and need to be captured and saved to this directory (`public/images/documentation/`).

## Capture Instructions
- Screenshots should be captured at 1920x1080 or similar resolution
- Use Chrome DevTools to set a consistent viewport if needed
- Capture in light mode by default (dark mode examples optional)
- Save as PNG format for best quality
- Keep file sizes reasonable (under 500KB each if possible)

## Required Screenshots

### Service Management
1. **service-management-list.png** - Service Management list view showing services with contract badges
2. **service-contract-association.png** - Service create/edit form showing the "Funding Contracts" checkbox section

### Contract Management
3. **contract-management-list.png** - Contract Management list view showing contracts with status indicators

### Contract Utilization Dashboard
4. **contract-utilization-dashboard.png** - Main Contract Utilization Dashboard showing card view with color-coded utilization
5. **contract-utilization-filters.png** - Filter and sort options on the Contract Utilization Dashboard

### Housing/Projects
6. **project-management-list.png** - Project Management list view showing projects with status and budget info
7. **project-budget-section.png** - Budget section within a project showing the fund code dropdown

### Project Budgets & Expenses
8. **project-budget-management.png** - Full budget management section with budget items listed
9. **project-budget-form.png** - Budget/expense form showing the searchable fund code dropdown

### Project Reporting
10. **project-reporting-dashboard.png** - Project Reporting dashboard showing summary metrics and filters

### Communications Log
11. **communications-log-list.png** - Communications Log list view with follow-up indicators
12. **communications-quick-create.png** - Quick create modal/form for participant, property, or contractor

## How to Capture

### Using Browser DevTools
1. Open the application in Chrome
2. Press F12 to open DevTools
3. Click the device toolbar icon or press Ctrl+Shift+M
4. Set viewport to 1920x1080
5. Navigate to the relevant page
6. Press Ctrl+Shift+P and type "screenshot" to capture full page or visible area

### Using Screenshot Tools
- On Linux: `gnome-screenshot -w` for window, or use Flameshot
- On Mac: Cmd+Shift+4 for selection
- On Windows: Win+Shift+S for snipping tool

## After Capturing
Once screenshots are added to this directory, run the documentation seeder to update the database:

```bash
php artisan db:seed --class=DocumentationContentSeeder
```

The images will be displayed in the documentation articles automatically via the HTML img tags embedded in the article content.
