// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import CustomUrl from './containers/Form/fields/CustomUrl';
import CustomUrlsDomainSelect from './containers/Form/fields/CustomUrlsDomainSelect';
import CustomUrlsLocaleSelect from './containers/Form/fields/CustomUrlsLocaleSelect';

fieldRegistry.add('custom_url', CustomUrl);
fieldRegistry.add('custom_urls_domain_select', CustomUrlsDomainSelect);
fieldRegistry.add('custom_urls_locale_select', CustomUrlsLocaleSelect);
