/**
 * Save Interceptor Plugin Registration
 *
 * Registers a hidden plugin that manages save interception.
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { registerPlugin } from '@wordpress/plugins';
import { SaveInterceptor } from './components/SaveInterceptor';
import type { PluginData } from './types';

/**
 * Save Interceptor Plugin Component
 */
const SaveInterceptorPlugin = () => {
    // Get plugin data from global
    const data: PluginData = window.dgwPendingRevisionsEditorData || {};

    return <SaveInterceptor data={data} />;
};

// Register the save interceptor plugin
registerPlugin('dgw-pending-revisions-save-interceptor', {
    render: SaveInterceptorPlugin,
});

export default SaveInterceptorPlugin;