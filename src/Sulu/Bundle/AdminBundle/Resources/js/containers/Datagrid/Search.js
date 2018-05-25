// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Input from '../../components/Input/Input';
import {translate} from '../../utils/Translator';

type Props = {
    onSearch: (search: ?string) => void,
};

@observer
export default class Search extends React.Component<Props> {
    @observable collapsed: boolean = true;
    @observable value: ?string = undefined;

    @action setCollapsed(collapsed: boolean) {
        this.collapsed = collapsed;
    }

    @action setValue(value: ?string) {
        this.value = value;
    }

    handleChange = (value: ?string) => {
        this.setValue(value);
    };

    handleKeyPress = (key: ?string) => {
        if (key === 'Enter') {
            this.handleSearch();
        }
    };

    handleSearch = () => {
        if (!this.collapsed && !this.value) {
            this.setCollapsed(true);
        }

        this.props.onSearch(this.value);
    };

    handleBlur = () => {
        this.handleSearch();
    };

    handleIconClick = () => {
        if (this.collapsed) {
            this.setCollapsed(false);
        }
    };

    handleClearClick = () => {
        this.setValue(undefined);
        this.handleSearch();
    };

    render() {
        return (
            <Input
                icon="su-search"
                collapsed={this.collapsed}
                onBlur={this.handleBlur}
                onChange={this.handleChange}
                onIconClick={this.handleIconClick}
                onKeyPress={this.handleKeyPress}
                onClearClick={this.handleClearClick}
                skin="dark"
                placeholder={translate('sulu_admin.datagrid_search_placeholder')}
                value={this.value}
            />
        );
    }
}
