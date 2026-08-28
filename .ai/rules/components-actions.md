---
paths:
  - 'app/{Components,Actions}/**'
---

# Components Actions

## Store phone numbers in international format
All stored phone_number values are international (e.g. "+41 79 123 45 67"). Validate user input with the country-less `phone` rule (rejects national like "079..."); phone country belongs to the number, never derive it from country_of_residence. Athlete wizard keeps Swiss-national input UI but normalizes via CreateDonationAction::formatPhoneNumber($number, 'CH') before storage. Legacy national values load raw into edit forms and intentionally block saves until corrected.
