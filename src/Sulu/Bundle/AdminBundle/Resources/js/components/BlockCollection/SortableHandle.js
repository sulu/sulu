// @flow
import React from 'react';
import {SortableHandle} from 'react-sortable-hoc';
import Icon from '../Icon';
import sortableHandleStyles from './sortableHandle.scss';

export default SortableHandle(() => <Icon className={sortableHandleStyles.sortableHandle} name="su-more" />);
