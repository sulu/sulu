// @flow
import {bundleReady, initializer} from 'sulu-admin-bundle/services';
import {
    blockPreviewTransformerRegistry,
    datagridAdapterRegistry,
    fieldRegistry,
    viewRegistry,
} from 'sulu-admin-bundle/containers';
import {MediaCardOverviewAdapter, MediaCardSelectionAdapter} from './containers/Datagrid';
import {MediaSelection, SingleMediaUpload, SingleMediaSelection} from './containers/Form';
import {
    MediaSelectionBlockPreviewTransformer,
    SingleMediaSelectionBlockPreviewTransformer,
} from './containers/FieldBlocks';
import MediaOverview from './views/MediaOverview';
import MediaDetails from './views/MediaDetails';
import MediaHistory from './views/MediaHistory';
import MediaFormats from './views/MediaFormats';

initializer.addUpdateConfigHook('sulu_media', (config: Object) => {
    viewRegistry.add('sulu_media.overview', MediaOverview);
    viewRegistry.add('sulu_media.details', MediaDetails);
    viewRegistry.add('sulu_media.formats', MediaFormats);
    viewRegistry.add('sulu_media.history', MediaHistory);

    datagridAdapterRegistry.add('media_card_overview', MediaCardOverviewAdapter);
    datagridAdapterRegistry.add('media_card_selection', MediaCardSelectionAdapter);

    fieldRegistry.add('media_selection', MediaSelection);
    fieldRegistry.add('single_media_selection', SingleMediaSelection);
    fieldRegistry.add('single_media_upload', SingleMediaUpload);

    const imageFormatUrl = config.endpoints.image_format;
    blockPreviewTransformerRegistry.add('media_selection', new MediaSelectionBlockPreviewTransformer(imageFormatUrl));
    blockPreviewTransformerRegistry.add(
        'single_media_selection',
        new SingleMediaSelectionBlockPreviewTransformer(imageFormatUrl)
    );
});

bundleReady();
