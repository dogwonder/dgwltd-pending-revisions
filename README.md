# DGW.ltd Pending Revisions

A modern WordPress plugin for managing pending revisions and draft workflows. Built with modern WordPress standards, Block Editor integration, and React-based admin interfaces.

## Features

- **Block Editor Integration**: Native Gutenberg support with sidebar plugins and document panels
- **Modern JavaScript**: Built with React, TypeScript, and wp-scripts
- **REST API**: Comprehensive API endpoints for all functionality
- **Revision Management**: Track accepted revisions and pending changes
- **Flexible Editing Modes**: Open, Requires Approval, and Locked modes
- **Content Filtering**: Display accepted revision content on frontend
- **Admin Dashboard**: React-based interface for managing revisions
- **User Capabilities**: Granular permission system

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Node.js 18.0 or higher (for development)

## Installation

1. Clone or download the plugin files
2. Place in your WordPress `wp-content/plugins/` directory
3. Run `npm install` to install dependencies
4. Run `npm run build` to build assets
5. Activate the plugin in WordPress admin

## Development

### Build Commands

- `npm run develop` - Start development with file watching
- `npm run build` - Build for production
- `npm run lint:js` - Lint JavaScript/TypeScript files
- `npm run lint:css` - Lint CSS files
- `npm run test:unit` - Run unit tests

### Project Structure

```
dgwltd-pending-revisions/
├── src/                    # Modern JS/React source files
├── build/                  # Compiled assets
├── includes/               # PHP classes
│   ├── Admin/             # Admin functionality
│   ├── API/               # REST API controllers
│   ├── Core/              # Core plugin classes
│   ├── Database/          # Database repositories
│   ├── Frontend/          # Public-facing functionality
│   └── Utils/             # Utility classes
├── admin/                  # Traditional admin files
├── public/                 # Public assets
├── languages/              # Translation files
└── tests/                  # Unit and integration tests
```

## Usage

### Basic Setup

1. Go to Settings > Pending Revisions
2. Configure editing modes for each post type
3. Set up user capabilities as needed

### Editing Modes

- **Open**: All changes are published immediately
- **Requires Approval**: Changes need approval from editors
- **Locked**: Only editors can make changes


### User capabilities

Custom capability `accept_revisions` to editor, and administrator.

✅ Authors: Can create and submit revisions for approval
✅ Editors & Admins: Can approve, reject, and publish revisions
✅ UI: Only shows approve/reject buttons to permitted users

### Block Editor

The plugin adds a sidebar panel in the Block Editor showing:
- Current revision status
- Pending revisions count
- Quick approval actions (for editors)

### REST API

The plugin provides REST API endpoints at `/wp-json/dgw-pending-revisions/v1/`:

- `GET /revisions/{post_id}` - Get revisions for a post
- `POST /revisions/{post_id}/{revision_id}/approve` - Approve a revision
- `POST /revisions/{post_id}/{revision_id}/reject` - Reject a revision
- `GET /revisions/pending` - Get all pending revisions

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass and code follows standards
6. Submit a pull request

## License

GPL v2 or later

## Credits

Inspired by and compatible with the original `fabrica-pending-revisions` plugin by Fabrica.

Built with modern WordPress standards and best practices.