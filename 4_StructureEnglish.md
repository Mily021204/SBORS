BEGIN

    DISPLAY "Web-Based Sangguniang Bayan Ordinances and Resolutions Retrieval System"

    SET system_status = ACTIVE

    DO WHILE system_status = ACTIVE

        DISPLAY Main Menu
            1. Public View
            2. Admin Login
            3. Exit

        RECEIVE user_choice

        IF user_choice = Public View THEN

            DISPLAY Search Page

            RECEIVE search_keyword
                (Document Number, Title, Keyword, Year, Category)

            IF search_keyword is NOT EMPTY THEN

                SEARCH Ordinances and Resolutions Database

                IF matching document EXISTS THEN

                    DISPLAY list of matching documents

                    RECEIVE selected_document

                    RETRIEVE selected document

                    DISPLAY
                        - Document Number
                        - Title
                        - Type (Ordinance/Resolution)
                        - Year Enacted
                        - Description
                        - Status
                        - Download/View Document

                ELSE

                    DISPLAY "No matching document found."

                END IF

            ELSE

                DISPLAY "Please enter a search keyword."

            END IF

        ELSE IF user_choice = Admin Login THEN

            RECEIVE username
            RECEIVE password

            IF login credentials are VALID THEN

                DISPLAY Admin Dashboard

                DISPLAY
                    1. Add Ordinance/Resolution
                    2. Update Document
                    3. Delete Document
                    4. Manage Users
                    5. Generate Reports
                    6. Logout

                RECEIVE admin_choice

                IF admin_choice = Add Ordinance/Resolution THEN

                    RECEIVE document information

                    VALIDATE document information

                    SAVE document TO Database

                    DISPLAY "Document successfully added."

                ELSE IF admin_choice = Update Document THEN

                    SELECT existing document

                    MODIFY document information

                    UPDATE Database

                    DISPLAY "Document successfully updated."

                ELSE IF admin_choice = Delete Document THEN

                    SELECT document

                    CONFIRM deletion

                    DELETE document FROM Database

                    DISPLAY "Document successfully deleted."

                ELSE IF admin_choice = Manage Users THEN

                    ADD, UPDATE, or REMOVE user accounts

                    SAVE changes

                ELSE IF admin_choice = Generate Reports THEN

                    GENERATE system reports

                    DISPLAY report summary

                ELSE

                    LOGOUT administrator

                END IF

            ELSE

                DISPLAY "Invalid username or password."

            END IF

        ELSE IF user_choice = Exit THEN

            SET system_status = INACTIVE

            DISPLAY "Thank you for using the system."

        ELSE

            DISPLAY "Invalid menu selection."

        END IF

    END DO

END
