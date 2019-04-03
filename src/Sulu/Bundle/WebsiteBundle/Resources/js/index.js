// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import AnalyticsDomainSelect from './containers/Form/fields/AnalyticsDomainSelect';

fieldRegistry.add('analytics_domain_select', AnalyticsDomainSelect);

bundleReady();
