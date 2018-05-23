// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import Input from '../../components/Input/Input';
import {translate} from '../../utils/Translator';

type Props = {
    onSearch: (search: ?string) => void,
};

@observer
export default class Search extends React.Component<Props> {
    @observable expanded: boolean = false;
    @observable value: ?string = undefined;

    @action setExpanded(expanded: boolean) {
        this.expanded = expanded;
    }

    @action setValue(value: ?string) {
        this.value = value;
    }

    @computed get isExpanded(): boolean {
        if (this.value && this.value.length > 0) {
            return true;
        }

        return this.expanded;
    }

    handleChange = (value: ?string) => {
        this.setValue(value);
    };

    handleKeyPress = (event: SyntheticKeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            this.handleSearch();
        }
    };

    handleSearch = () => {
        this.props.onSearch(this.value);
    };

    handleBlur = () => {
        if (this.expanded && !this.value) {
            this.setExpanded(false);
        }

        this.handleSearch();
    };

    handleIconClick = () => {
        if (this.expanded) {
            this.handleSearch();

            return;
        }

        this.setExpanded(!this.expanded);
    };

    handleClearClick = () => {
        this.setValue(undefined);
    };

    render() {
        return (
            <Input
                icon="su-search"
                expanded={this.expanded}
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
