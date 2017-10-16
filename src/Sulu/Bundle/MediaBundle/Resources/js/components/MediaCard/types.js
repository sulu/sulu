// @flow
import type {Element} from 'react';
import {Menu} from 'sulu-admin-bundle/components';
import DownloadListItem from './DownloadListItem';

type DownloadItem = Element<typeof DownloadListItem>;

type DividerItem = Element<typeof Menu.Divider>;

type DownloadItems = Array<Element<typeof DownloadListItem>>;

export type DownloadItemsList = Array<DownloadItem | DividerItem | DownloadItems>;
