// @flow
import React, {Component, Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {Icon, Input, Popover} from '../../components';
import languageSelectStyles from './language-select.scss';
import type {ElementRef} from 'react';
import type {LanguageType} from './types';

type Props = {|
    ariaLabel: string,
    languages: Array<LanguageType>,
    messages: {
        allLanguages: string,
        searchLanguages: string,
        suggestedLanguages: string,
    },
    onChange: (locale: string) => void,
    suffix?: ?string,
    /** Locales configured for the current webspace, offered before the complete list. */
    suggestedLocales: Array<string>,
    value: ?string,
|};

/**
 * @internal
 *
 * Picking a target language means finding one entry among more than a hundred, which a plain select does not
 * support. The languages are offered in a searchable grid instead, with the ones used by the webspace first.
 */
@observer
class LanguageSelect extends Component<Props> {
    @observable open: boolean = false;
    @observable searchTerm: string = '';
    @observable buttonRef: ?ElementRef<*>;

    setButtonRef = (ref: ?ElementRef<*>) => {
        this.setButtonRefAction(ref);
    };

    @action setButtonRefAction = (ref: ?ElementRef<*>) => {
        this.buttonRef = ref;
    };

    @action handleButtonClick = () => {
        this.open = !this.open;
        this.searchTerm = '';
    };

    @action handleClose = () => {
        this.open = false;
    };

    @action handleSearchChange = (searchTerm: ?string) => {
        this.searchTerm = searchTerm || '';
    };

    handleLanguageClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        const {onChange} = this.props;
        const locale = event.currentTarget.dataset.locale;

        this.handleClose();

        if (locale) {
            onChange(locale);
        }
    };

    @computed get matchingLanguages(): Array<LanguageType> {
        const {languages} = this.props;
        const searchTerm = this.searchTerm.trim().toLowerCase();

        if (!searchTerm) {
            return languages;
        }

        return languages.filter((language) => language.label.toLowerCase().includes(searchTerm));
    }

    @computed get suggestedLanguages(): Array<LanguageType> {
        const {suggestedLocales} = this.props;
        const suggested = suggestedLocales.map(normalizeLocale);
        const suggestedBases = suggested.map(baseLocale);

        return this.matchingLanguages.filter((language) => {
            const locale = normalizeLocale(language.locale);

            // a webspace configured for "en" is served by every english variant, "en-gb" among them
            return suggested.includes(locale) || suggestedBases.includes(baseLocale(locale));
        });
    }

    @computed get remainingLanguages(): Array<LanguageType> {
        const suggested = this.suggestedLanguages;

        return this.matchingLanguages.filter((language) => !suggested.includes(language));
    }

    @computed get selectedLanguage(): ?LanguageType {
        const {languages, value} = this.props;

        return languages.find((language) => language.locale.toLowerCase() === value?.toLowerCase());
    }

    renderLanguages = (languages: Array<LanguageType>) => {
        const {value} = this.props;

        return (
            <div className={languageSelectStyles.grid}>
                {languages.map((language) => {
                    const selected = language.locale.toLowerCase() === value?.toLowerCase();

                    return (
                        <button
                            className={classNames(
                                languageSelectStyles.language,
                                {[languageSelectStyles.selected]: selected}
                            )}
                            data-locale={language.locale}
                            key={language.locale}
                            onClick={this.handleLanguageClick}
                            type="button"
                        >
                            {selected && <Icon className={languageSelectStyles.check} name="su-check" />}
                            {renderLabel(language.label)}
                        </button>
                    );
                })}
            </div>
        );
    };

    render() {
        const {
            ariaLabel,
            messages: {
                allLanguages: allLanguagesMessage,
                searchLanguages: searchLanguagesMessage,
                suggestedLanguages: suggestedLanguagesMessage,
            },
            suffix,
        } = this.props;

        const selectedLanguage = this.selectedLanguage;

        return (
            <Fragment>
                <button
                    aria-label={ariaLabel}
                    className={classNames(
                        languageSelectStyles.button,
                        {[languageSelectStyles.buttonOpen]: this.open}
                    )}
                    onClick={this.handleButtonClick}
                    ref={this.setButtonRef}
                    type="button"
                >
                    {selectedLanguage ? renderLabel(selectedLanguage.label) : null}
                    {suffix && <span className={languageSelectStyles.suffix}>{' (' + suffix + ')'}</span>}
                    <Icon className={languageSelectStyles.angle} name={this.open ? 'su-angle-up' : 'su-angle-down'} />
                </button>

                {this.buttonRef &&
                    <Popover
                        anchorElement={this.buttonRef}
                        onClose={this.handleClose}
                        open={this.open}
                        verticalOffset={8}
                    >
                        {(setPopoverRef, popoverStyles) => (
                            <div
                                className={languageSelectStyles.popover}
                                ref={setPopoverRef}
                                style={popoverStyles}
                            >
                                <div className={languageSelectStyles.search}>
                                    <Input
                                        icon="su-search"
                                        onChange={this.handleSearchChange}
                                        placeholder={searchLanguagesMessage}
                                        value={this.searchTerm}
                                    />
                                </div>

                                <div className={languageSelectStyles.list}>

                                    {this.suggestedLanguages.length > 0 &&
                                        <Fragment>
                                            <div className={languageSelectStyles.sectionTitle}>
                                                {suggestedLanguagesMessage}
                                            </div>
                                            {this.renderLanguages(this.suggestedLanguages)}
                                        </Fragment>
                                    }

                                    {this.remainingLanguages.length > 0 &&
                                        <Fragment>
                                            <div className={languageSelectStyles.sectionTitle}>
                                                {allLanguagesMessage}
                                            </div>
                                            {this.renderLanguages(this.remainingLanguages)}
                                        </Fragment>
                                    }
                                </div>
                            </div>
                        )}
                    </Popover>
                }
            </Fragment>
        );
    }
}

/**
 * Labels carry their variant in brackets, for example "English (British)". The variant is set apart so the
 * language itself stays readable in a dense grid.
 */
function normalizeLocale(locale: string): string {
    return locale.toLowerCase().replace(/_/g, '-');
}

function baseLocale(locale: string): string {
    return normalizeLocale(locale).split('-')[0];
}

function renderLabel(label: string) {
    const match = label.match(/^([^(]+)\s*(\(.+\))$/);

    if (!match) {
        return <span className={languageSelectStyles.name}>{label}</span>;
    }

    return (
        <Fragment>
            <span className={languageSelectStyles.name}>{match[1].trim()}</span>
            <span className={languageSelectStyles.variant}>{match[2]}</span>
        </Fragment>
    );
}

export default LanguageSelect;
