// @flow
import {when} from 'mobx';
import {initializer} from 'sulu-admin-bundle/services';
import {
    blockPreviewTransformerRegistry,
    listAdapterRegistry,
    fieldRegistry,
    internalLinkTypeRegistry,
    viewRegistry,
} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import {TeaserSelection} from 'sulu-page-bundle/containers';
import {MediaInternalLinkTypeOverlay} from './containers/CKEditor5';
import {MediaCardOverviewAdapter, MediaCardSelectionAdapter} from './containers/List';
import {MediaSelection, MediaVersionUpload, SingleMediaUpload, SingleMediaSelection} from './containers/Form';
import {
    MediaSelectionBlockPreviewTransformer,
    SingleMediaSelectionBlockPreviewTransformer,
} from './containers/FieldBlocks';
import MediaCollection from './containers/MediaCollection';
import MediaOverview from './views/MediaOverview';
import MediaHistory from './views/MediaHistory';
import MediaFormats from './views/MediaFormats';

const FIELD_TYPE_MEDIA_SELECTION = 'media_selection';
const FIELD_TYPE_SINGLE_MEDIA_SELECTION = 'single_media_selection';

initializer.addUpdateConfigHook('sulu_media', (config: Object, initialized: boolean) => {
    const {media_permissions: mediaPermissions} = config;

    MediaCollection.addable = mediaPermissions.add;
    MediaCollection.deletable = mediaPermissions.delete;
    MediaCollection.editable = mediaPermissions.edit;
    MediaCollection.securable = mediaPermissions.security;

    if (initialized) {
        return;
    }

    viewRegistry.add('sulu_media.overview', MediaOverview);
    viewRegistry.add('sulu_media.formats', MediaFormats);
    viewRegistry.add('sulu_media.history', MediaHistory);

    listAdapterRegistry.add('media_card_overview', MediaCardOverviewAdapter);
    listAdapterRegistry.add('media_card_selection', MediaCardSelectionAdapter);

    fieldRegistry.add(FIELD_TYPE_MEDIA_SELECTION, MediaSelection);
    fieldRegistry.add(FIELD_TYPE_SINGLE_MEDIA_SELECTION, SingleMediaSelection);
    fieldRegistry.add('single_media_upload', SingleMediaUpload);
    fieldRegistry.add('media_version_upload', MediaVersionUpload);

    const imageFormatUrl = config.endpoints.image_format;
    blockPreviewTransformerRegistry.add(
        FIELD_TYPE_MEDIA_SELECTION,
        new MediaSelectionBlockPreviewTransformer(imageFormatUrl),
        2048
    );
    blockPreviewTransformerRegistry.add(
        FIELD_TYPE_SINGLE_MEDIA_SELECTION,
        new SingleMediaSelectionBlockPreviewTransformer(imageFormatUrl),
        2048
    );

    TeaserSelection.Item.mediaUrl = imageFormatUrl + '?locale=en&format=sulu-25x25';

    when(
        () => !!initializer.initializedTranslationsLocale,
        (): void => {
            internalLinkTypeRegistry.add('media', MediaInternalLinkTypeOverlay, translate('sulu_media.media'));
        }
    );
});
