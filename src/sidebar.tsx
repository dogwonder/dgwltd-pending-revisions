/**
 * Block Editor Sidebar Plugin
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { backup } from '@wordpress/icons';
import { PendingRevisionsPanel } from './components/PendingRevisionsPanel';

/**
 * Block Editor Sidebar Plugin
 *
 * Registers a sidebar plugin in the Block Editor for managing pending revisions.
 * Provides an interface for reviewing, approving, and managing revisions.
 */
const PendingRevisionsSidebar = () => {
    return (
        <>
            <PluginSidebarMoreMenuItem
                target="dgw-pending-revisions-sidebar"
                icon={backup}
            >
                {__('Pending Revisions', 'dgwltd-pending-revisions')}
            </PluginSidebarMoreMenuItem>
            <PluginSidebar
                name="dgw-pending-revisions-sidebar"
                icon={backup}
                title={__('Pending Revisions', 'dgwltd-pending-revisions')}
                className="dgw-pending-revisions-sidebar"
            >
                <PendingRevisionsPanel />
            </PluginSidebar>
        </>
    );
};

// Register the plugin
registerPlugin('dgw-pending-revisions-sidebar', {
    render: PendingRevisionsSidebar,
    icon: backup,
});

export default PendingRevisionsSidebar;