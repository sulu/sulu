// @flow
import {viewRegistry} from 'sulu-admin-bundle/containers';
import {bundleReady} from 'sulu-admin-bundle/services';
import SnippetAreas from './views/SnippetAreas';

viewRegistry.add('sulu_snippet.snippet_areas', SnippetAreas);

bundleReady();
