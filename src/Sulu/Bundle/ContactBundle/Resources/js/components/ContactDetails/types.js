// @flow

type Email = {|
    email: ?string,
    emailType: number,
|};

type Fax = {|
    fax: ?string,
    faxType: number,
|};

type Phone = {|
    phone: ?string,
    phoneType: number,
|};

type SocialMedia = {|
    socialMediaType: number,
    username: ?string,
|};

type Website = {|
    website: ?string,
    websiteType: number,
|};

export type ContactDetailsValue = {|
    emails: Array<Email>,
    faxes: Array<Fax>,
    phones: Array<Phone>,
    socialMedia: Array<SocialMedia>,
    websites: Array<Website>,
|};
