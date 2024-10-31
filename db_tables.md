# Blue Collar Pets Database Structure

## Animals Table
Stores information about all available pets.

| Column Name    | Data Type    | Description                                    |
|---------------|--------------|------------------------------------------------|
| animal_id     | String       | Unique identifier for each animal              |
| name          | String       | Name of the animal                             |
| species       | String       | Type of animal (dog, cat, etc.)               |
| breed         | String       | Breed of the animal                            |
| age_years     | Decimal      | Age of the animal in years                     |
| gender        | String       | Gender of the animal (M/F)                     |
| date_added    | Date         | Date when animal was added to the system       |
| status        | String       | Available, Pending, or Adopted                 |
| description   | Text         | Detailed description of the animal             |
| health_status | Text         | Health information and medical history         |
| location_id   | String       | Reference to the location where animal is kept |
| image_url     | String       | URL to the animal's primary image              |
| is_featured   | Boolean      | Whether the animal is featured on homepage     |

## Adoption Applications Table
Tracks adoption applications submitted by potential adopters.

| Column Name      | Data Type    | Description                                    |
|-----------------|--------------|------------------------------------------------|
| application_id  | String       | Unique identifier for each application         |
| animal_id       | String       | Reference to the animal being applied for      |
| first_name      | String       | Applicant's first name                         |
| last_name       | String       | Applicant's last name                          |
| email          | String       | Applicant's email address                      |
| phone          | String       | Applicant's phone number                       |
| address        | Text         | Applicant's full address                       |
| employer       | String       | Applicant's employer                           |
| home_type      | String       | Type of home (house, apartment, etc.)          |
| home_habits    | Text         | Description of home environment                |
| existing_pets  | Text         | Information about existing pets                |
| status         | String       | Pending, Approved, or Rejected                 |
| submission_date| Timestamp    | When the application was submitted             |

## Locations Table
Stores information about Blue Collar Pets store locations.

| Column Name        | Data Type    | Description                                    |
|-------------------|--------------|------------------------------------------------|
| location_id       | String       | Unique identifier for each location            |
| name              | String       | Name of the location                           |
| address           | Text         | Street address                                 |
| city              | String       | City name                                      |
| state             | String       | State code                                     |
| zip               | String       | ZIP code                                       |
| phone             | String       | Location's phone number                        |
| email             | String       | Location's email address                       |
| hours_of_operation| Text         | Business hours                                 |
| is_active         | Boolean      | Whether the location is currently active       |

## Reviews Table
Stores customer reviews and ratings.

| Column Name     | Data Type    | Description                                    |
|----------------|--------------|------------------------------------------------|
| review_id      | String       | Unique identifier for each review              |
| customer_name  | String       | Name of the reviewer                           |
| rating         | Integer      | Rating (1-5)                                   |
| comment        | Text         | Review content                                 |
| location_id    | String       | Reference to the reviewed location             |
| submission_date| Timestamp    | When the review was submitted                  |
| is_approved    | Boolean      | Whether the review is approved for display     |

## Contact Inquiries Table
Stores questions and inquiries from the contact form.

| Column Name     | Data Type    | Description                                    |
|----------------|--------------|------------------------------------------------|
| inquiry_id     | String       | Unique identifier for each inquiry             |
| first_name     | String       | Inquirer's first name                          |
| last_name      | String       | Inquirer's last name                           |
| email          | String       | Inquirer's email address                       |
| phone          | String       | Inquirer's phone number                        |
| message        | Text         | Content of the inquiry                         |
| submission_date| Timestamp    | When the inquiry was submitted                 |
| status         | String       | New, In Progress, or Resolved                  |

## Table Relationships

1. Animals → Locations
    - Each animal belongs to one location (location_id)

2. Adoption Applications → Animals
    - Each application is for one specific animal (animal_id)

3. Reviews → Locations
    - Each review can be associated with one location (location_id)
