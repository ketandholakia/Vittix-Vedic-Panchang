class Mercury(object):
    """
    Class Mercury models that planet.
    """

    @staticmethod
    def geometric_heliocentric_position(epoch, tofk5=True):
        """This method computes the geometric heliocentric position of planet
        Mercury for a given epoch, using the VSOP87 theory.

        :param epoch: Epoch to compute Mercury position, as an Epoch object
        :type epoch: :py:class:`Epoch`
        :param tofk5: Whether or not the small correction to convert to the FK5
            system will be applied or not
        :type tofk5: bool

        :returns: A tuple with the heliocentric longitude and latitude (as
            :py:class:`Angle` objects), and the radius vector (as a float,
            in astronomical units), in that order
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> epoch = Epoch(2018, 10, 27.0)
        >>> l, b, r = Mercury.geometric_heliocentric_position(epoch)
        >>> print(round(l.to_positive(), 4))
        287.4887
        >>> print(round(b, 4))
        -6.0086
        >>> print(round(r, 5))
        0.45113
        """

        return geometric_vsop_pos(epoch, VSOP87_L, VSOP87_B, VSOP87_R, tofk5)

    @staticmethod
    def apparent_heliocentric_position(epoch):
        """This method computes the apparent heliocentric position of planet
        Mercury for a given epoch, using the VSOP87 theory.

        :param epoch: Epoch to compute Mercury position, as an Epoch object
        :type epoch: :py:class:`Epoch`

        :returns: A tuple with the heliocentric longitude and latitude (as
            :py:class:`Angle` objects), and the radius vector (as a float,
            in astronomical units), in that order
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.
        """

        return apparent_vsop_pos(epoch, VSOP87_L, VSOP87_B, VSOP87_R)

    @staticmethod
    def orbital_elements_mean_equinox(epoch):
        """This method computes the orbital elements of Mercury for the mean
        equinox of the date for a given epoch.

        :param epoch: Epoch to compute orbital elements, as an Epoch object
        :type epoch: :py:class:`Epoch`

        :returns: A tuple containing the following six orbital elements:
            - Mean longitude of the planet (Angle)
            - Semimajor axis of the orbit (float, astronomical units)
            - eccentricity of the orbit (float)
            - inclination on the plane of the ecliptic (Angle)
            - longitude of the ascending node (Angle)
            - argument of the perihelion (Angle)
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> epoch = Epoch(2065, 6, 24.0)
        >>> l, a, e, i, ome, arg = Mercury.orbital_elements_mean_equinox(epoch)
        >>> print(round(l, 6))
        203.494701
        >>> print(round(a, 8))
        0.38709831
        >>> print(round(e, 7))
        0.2056451
        >>> print(round(i, 6))
        7.006171
        >>> print(round(ome, 5))
        49.10765
        >>> print(round(arg, 6))
        29.367732
        """

        return orbital_elements(epoch, ORBITAL_ELEM, ORBITAL_ELEM)

    @staticmethod
    def orbital_elements_j2000(epoch):
        """This method computes the orbital elements of Mercury for the
        standard equinox J2000.0 for a given epoch.

        :param epoch: Epoch to compute orbital elements, as an Epoch object
        :type epoch: :py:class:`Epoch`

        :returns: A tuple containing the following six orbital elements:
            - Mean longitude of the planet (Angle)
            - Semimajor axis of the orbit (float, astronomical units)
            - eccentricity of the orbit (float)
            - inclination on the plane of the ecliptic (Angle)
            - longitude of the ascending node (Angle)
            - argument of the perihelion (Angle)
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> epoch = Epoch(2065, 6, 24.0)
        >>> l, a, e, i, ome, arg = Mercury.orbital_elements_j2000(epoch)
        >>> print(round(l, 6))
        202.579453
        >>> print(round(a, 8))
        0.38709831
        >>> print(round(e, 7))
        0.2056451
        >>> print(round(i, 6))
        7.001089
        >>> print(round(ome, 5))
        48.24873
        >>> print(round(arg, 6))
        29.311401
        """

        return orbital_elements(epoch, ORBITAL_ELEM, ORBITAL_ELEM_J2000)

    @staticmethod
    def geocentric_position(epoch):
        """This method computes the geocentric position of Mercury (right
        ascension and declination) for the given epoch, as well as the
        elongation angle.

        :param epoch: Epoch to compute geocentric position, as an Epoch object
        :type epoch: :py:class:`Epoch`

        :returns: A tuple containing the right ascension, the declination and
            the elongation angle as Angle objects
        :rtype: tuple
        :raises: TypeError if input value is of wrong type.

        >>> epoch = Epoch(1992, 12, 20.0)
        >>> ra, dec, elon = Mercury.geocentric_position(epoch)
        >>> print(ra.ra_str(n_dec=1))
        16h 33' 59.3''
        >>> print(dec.dms_str(n_dec=1))
        -20d 53' 31.6''
        >>> print(elon.dms_str(n_dec=1))
        18d 24' 29.8''
        """

        # First check that input value is of correct types
        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input type")
        # Compute the heliocentric position of Mercury
        l, b, r = Mercury.geometric_heliocentric_position(epoch, tofk5=False)
        # Compute the heliocentric position of the Earth
        l0, b0, r0 = Earth.geometric_heliocentric_position(epoch, tofk5=False)
        # Convert to radians
        lr = l.rad()
        br = b.rad()
        l0r = l0.rad()
        b0r = b0.rad()
        # Compute first iteration
        x = r * cos(br) * cos(lr) - r0 * cos(b0r) * cos(l0r)
        y = r * cos(br) * sin(lr) - r0 * cos(b0r) * sin(l0r)
        z = r * sin(br) - r0 * sin(b0r)
        delta = sqrt(x * x + y * y + z * z)
        tau = 0.0057755183 * delta
        # Adjust the epoch for light-time
        epoch -= tau
        # Compute again Mercury coordinates with this correction
        l, b, r = Mercury.geometric_heliocentric_position(epoch, tofk5=False)
        # Compute second iteration
        lr = l.rad()
        br = b.rad()
        x = r * cos(br) * cos(lr) - r0 * cos(b0r) * cos(l0r)
        y = r * cos(br) * sin(lr) - r0 * cos(b0r) * sin(l0r)
        z = r * sin(br) - r0 * sin(b0r)
        # Compute longitude and latitude
        lamb = atan2(y, x)
        beta = atan2(z, sqrt(x * x + y * y))
        # Now, let's compute the aberration effect
        t = (epoch - JDE2000) / 36525
        e = 0.016708634 + t * (-0.000042037 - t * 0.0000001267)
        pie = 102.93735 + t * (1.71946 + t * 0.00046)
        pie = radians(pie)
        lon = l0 + 180.0
        lon = lon.rad()
        k = 20.49552    # The constant of aberration
        deltal1 = k * (-cos(lon - lamb) + e * cos(pie - lamb)) / cos(beta)
        deltab1 = -k * sin(beta) * (sin(lon - lamb) - e * sin(pie - lamb))
        deltal1 = Angle(0, 0, deltal1)
        deltab1 = Angle(0, 0, deltab1)
        # Correction to FK5 system
        lamb = Angle(lamb, radians=True)
        lamb = lamb.to_positive()
        beta = Angle(beta, radians=True)
        l_prime = lamb - t * (1.397 + t * 0.00031)
        deltal2 = Angle(0, 0, -0.09033)
        a = 0.03916 * (cos(l_prime.rad()) + sin(l_prime.rad()))
        a = a * tan(b.rad())
        deltal2 += Angle(0, 0, a)
        deltab2 = 0.03916 * (cos(l_prime.rad()) - sin(l_prime.rad()))
        deltab2 = Angle(0, 0, deltab2)
        # Apply the corrections
        lamb = lamb + deltal1 + deltal2
        beta = beta + deltab1 + deltab2
        # Correction for nutation
        dpsi = nutation_longitude(epoch)
        lamb += dpsi
        e = true_obliquity(epoch)
        ra, dec = ecliptical2equatorial(lamb, beta, e)
        # Let's compute the elongation angle
        lons, lats, rs = Sun.apparent_geocentric_position(epoch)
        lambr = lamb.rad()
        lsr = lons.rad()
        betar = beta.rad()
        elon = acos(cos(betar) * cos(lambr - lsr))
        elon = Angle(elon, radians=True)
        return ra, dec, elon

    @staticmethod
    def inferior_conjunction(epoch):
        """This method computes the time of the inferior conjunction closest to
        the given epoch.

        :param epoch: Epoch close to the desired inferior conjunction
        :type epoch: :py:class:`Epoch`

        :returns: The time when the inferior conjunction happens, as an Epoch
        :rtype: :py:class:`Epoch`
        :raises: TypeError if input value is of wrong type.
        :raises: ValueError if input epoch outside the -2000/4000 range.

        >>> epoch = Epoch(1993, 10, 1.0)
        >>> conjunction = Mercury.inferior_conjunction(epoch)
        >>> y, m, d = conjunction.get_date()
        >>> print(y)
        1993
        >>> print(m)
        11
        >>> print(round(d, 4))
        6.1449
        >>> epoch = Epoch(1631, 10, 1.0)
        >>> conjunction = Mercury.inferior_conjunction(epoch)
        >>> y, m, d = conjunction.get_date()
        >>> print(y)
        1631
        >>> print(m)
        11
        >>> print(round(d, 3))
        7.306
        """

        # First check that input value is of correct types
        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input type")
        # Check that the input epoch is within valid range
        y = epoch.year()
        if y < -2000.0 or y > 4000.0:
            raise ValueError("Epoch outside the -2000/4000 range")
        # Set some specific constants for Mercury's inferior conjunction
        a = 2451612.023
        b = 115.8774771
        m0 = 63.5867
        m1 = 114.2088742
        k = round((365.2425 * y + 1721060.0 - a) / b)
        jde0 = a + k * b
        m = m0 + k * m1
        m = Angle(m).to_positive()
        m = m.rad()
        t = (jde0 - 2451545.0) / 36525.0
        corr = (0.0545 + 0.0002 * t
                + sin(m) * (-6.2008 + t * (0.0074 + t * 0.00003))
                + cos(m) * (-3.275 + t * (-0.0197 + t * 0.00001))
                + sin(2.0 * m) * (0.4737 + t * (-0.0052 - t * 0.00001))
                + cos(2.0 * m) * (0.8111 + t * (0.0033 - t * 0.00002))
                + sin(3.0 * m) * (0.0037 + t * 0.0018)
                + cos(3.0 * m) * (-0.1768 + t * t * 0.00001)
                + sin(4.0 * m) * (-0.0211 - t * 0.0004)
                + cos(4.0 * m) * (0.0326 - t * 0.0003)
                + sin(5.0 * m) * (0.0083 + t * 0.0001)
                + cos(5.0 * m) * (-0.004 + t * 0.0001))
        to_return = jde0 + corr
        return Epoch(to_return)

    @staticmethod
    def superior_conjunction(epoch):
        """This method computes the time of the superior conjunction closest to
        the given epoch.

        :param epoch: Epoch close to the desired superior conjunction
        :type epoch: :py:class:`Epoch`

        :returns: The time when the superior conjunction happens, as an Epoch
        :rtype: :py:class:`Epoch`
        :raises: TypeError if input value is of wrong type.
        :raises: ValueError if input epoch outside the -2000/4000 range.

        >>> epoch = Epoch(1993, 10, 1.0)
        >>> conjunction = Mercury.superior_conjunction(epoch)
        >>> y, m, d = conjunction.get_date()
        >>> print(y)
        1993
        >>> print(m)
        8
        >>> print(round(d, 4))
        29.3301
        """

        # First check that input value is of correct types
        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input type")
        # Check that the input epoch is within valid range
        y = epoch.year()
        if y < -2000.0 or y > 4000.0:
            raise ValueError("Epoch outside the -2000/4000 range")
        # Set some specific constants for Mercury's superior conjunction
        a = 2451554.084
        b = 115.8774771
        m0 = 6.4822
        m1 = 114.2088742
        k = round((365.2425 * y + 1721060.0 - a) / b)
        jde0 = a + k * b
        m = m0 + k * m1
        m = Angle(m).to_positive()
        m = m.rad()
        t = (jde0 - 2451545.0) / 36525.0
        corr = (-0.0548 - 0.0002 * t
                + sin(m) * (7.3894 + t * (-0.01 - t * 0.00003))
                + cos(m) * (3.22 + t * (0.0197 - t * 0.00001))
                + sin(2.0 * m) * (0.8383 + t * (-0.0064 - t * 0.00001))
                + cos(2.0 * m) * (0.9666 + t * (0.0039 - t * 0.00003))
                + sin(3.0 * m) * (0.077 - t * 0.0026)
                + cos(3.0 * m) * (0.2758 + t * (0.0002 - t * 0.00002))
                + sin(4.0 * m) * (-0.0128 - t * 0.0008)
                + cos(4.0 * m) * (0.0734 + t * (-0.0004 - t * 0.00001))
                + sin(5.0 * m) * (-0.0122 - t * 0.0002)
                + cos(5.0 * m) * (0.0173 - t * 0.0002))
        to_return = jde0 + corr
        return Epoch(to_return)

    @staticmethod
    def western_elongation(epoch):
        """This method computes the time of the western elongation closest to
        the given epoch, as well as the corresponding maximum elongation angle.

        :param epoch: Epoch close to the desired western elongation
        :type epoch: :py:class:`Epoch`

        :returns: A tuple with the time when the western elongation happens, as
            an Epoch, and the maximum elongation angle, as an Angle
        :rtype: tuple
        :raises: TypeError if input value is of wrong type.
        :raises: ValueError if input epoch outside the -2000/4000 range.

        >>> epoch = Epoch(1993, 11, 1.0)
        >>> time, elongation = Mercury.western_elongation(epoch)
        >>> y, m, d = time.get_date()
        >>> print(y)
        1993
        >>> print(m)
        11
        >>> print(round(d, 4))
        22.6386
        >>> print(round(elongation, 4))
        19.7506
        """

        # First check that input value is of correct types
        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input type")
        # Check that the input epoch is within valid range
        y = epoch.year()
        if y < -2000.0 or y > 4000.0:
            raise ValueError("Epoch outside the -2000/4000 range")
        # Set some specific constants for Mercury's inferior conjunction
        a = 2451612.023
        b = 115.8774771
        m0 = 63.5867
        m1 = 114.2088742
        k = round((365.2425 * y + 1721060.0 - a) / b)
        jde0 = a + k * b
        m = m0 + k * m1
        m = Angle(m).to_positive()
        m = m.rad()
        t = (jde0 - 2451545.0) / 36525.0
        corr = (21.6249 - 0.0002 * t
                + sin(m) * (0.1306 + t * 0.0065)
                + cos(m) * (-2.7661 + t * (-0.0011 + t * 0.00001))
                + sin(2.0 * m) * (0.2438 + t * (-0.0024 - t * 0.00001))
                + cos(2.0 * m) * (0.5767 + t * 0.0023)
                + sin(3.0 * m) * (0.1041)
                + cos(3.0 * m) * (-0.0184 + t * 0.0007)
                + sin(4.0 * m) * (-0.0051 - t * 0.0001)
                + cos(4.0 * m) * (0.0048 + t * 0.0001)
                + sin(5.0 * m) * (0.0026)
                + cos(5.0 * m) * (0.0037))
        elon = (22.4143 - 0.0001 * t
                + sin(m) * (4.3651 + t * (-0.0048 - t * 0.00002))
                + cos(m) * (2.3787 + t * (0.0121 - t * 0.00001))
                + sin(2.0 * m) * (0.2674 + t * 0.0022)
                + cos(2.0 * m) * (-0.3873 + t * (0.0008 + t * 0.00001))
                + sin(3.0 * m) * (-0.0369 - t * 0.0001)
                + cos(3.0 * m) * (0.0017 - t * 0.0001)
                + sin(4.0 * m) * (0.0059)
                + cos(4.0 * m) * (0.0061 + t * 0.0001)
                + sin(5.0 * m) * (0.0007)
                + cos(5.0 * m) * (-0.0011))
        elon = Angle(elon).to_positive()
        to_return = jde0 + corr
        return Epoch(to_return), elon

    @staticmethod
    def eastern_elongation(epoch):
        """This method computes the time of the eastern elongation closest to
        the given epoch, as well as the corresponding maximum elongation angle.

        :param epoch: Epoch close to the desired eastern elongation
        :type epoch: :py:class:`Epoch`

        :returns: A tuple with the time when the eastern elongation happens, as
            an Epoch, and the maximum elongation angle, as an Angle
        :rtype: tuple
        :raises: TypeError if input value is of wrong type.
        :raises: ValueError if input epoch outside the -2000/4000 range.

        >>> epoch = Epoch(1990, 8, 1.0)
        >>> time, elongation = Mercury.eastern_elongation(epoch)
        >>> y, m, d = time.get_date()
        >>> print(y)
        1990
        >>> print(m)
        8
        >>> print(round(d, 4))
        11.8514
        >>> print(round(elongation, 4))
        27.4201
        """

        # First check that input value is of correct types
        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input type")
        # Check that the input epoch is within valid range
        y = epoch.year()
        if y < -2000.0 or y > 4000.0:
            raise ValueError("Epoch outside the -2000/4000 range")
        # Set some specific constants for Mercury's inferior conjunction
        a = 2451612.023
        b = 115.8774771
        m0 = 63.5867
        m1 = 114.2088742
        k = round((365.2425 * y + 1721060.0 - a) / b)
        jde0 = a + k * b
        m = m0 + k * m1
        m = Angle(m).to_positive()
        m = m.rad()
        t = (jde0 - 2451545.0) / 36525.0
        corr = (-21.6101 + 0.0002 * t
                + sin(m) * (-1.9803 + t * (-0.006 + t * 0.00001))
                + cos(m) * (1.4151 + t * (-0.0072 - t * 0.00001))
                + sin(2.0 * m) * (0.5528 + t * (-0.0005 - t * 0.00001))
                + cos(2.0 * m) * (0.2905 + t * (0.0034 + t * 0.00001))
                + sin(3.0 * m) * (-0.1121 + t * (-0.0001 + t * 0.00001))
                + cos(3.0 * m) * (-0.0098 - t * 0.0015)
                + sin(4.0 * m) * (0.0192)
                + cos(4.0 * m) * (0.0111 + t * 0.0004)
                + sin(5.0 * m) * (-0.0061)
                + cos(5.0 * m) * (-0.0032 - t * 0.0001))
        elon = (22.4697
                + sin(m) * (-4.2666 + t * (0.0054 + t * 0.00002))
                + cos(m) * (-1.8537 - t * 0.0137)
                + sin(2.0 * m) * (0.3598 + t * (0.0008 - t * 0.00001))
                + cos(2.0 * m) * (-0.068 + t * 0.0026)
                + sin(3.0 * m) * (-0.0524 - t * 0.0003)
                + cos(3.0 * m) * (0.0052 - t * 0.0006)
                + sin(4.0 * m) * (0.0107 + t * 0.0001)
                + cos(4.0 * m) * (-0.0013 + t * 0.0001)
                + sin(5.0 * m) * (-0.0021)
                + cos(5.0 * m) * (0.0003))
        elon = Angle(elon).to_positive()
        to_return = jde0 + corr
        return Epoch(to_return), elon

    @staticmethod
    def station_longitude_1(epoch):
        """This method computes the time of the 1st station in longitude
        (i.e. when the planet is stationary and begins to move westward -
        retrograde - among the starts) closest to the given epoch.

        :param epoch: Epoch close to the desired inferior conjunction
        :type epoch: :py:class:`Epoch`

        :returns: Time when the 1st statin in longitude happens, as an Epoch
        :rtype: :py:class:`Epoch`
        :raises: TypeError if input value is of wrong type.
        :raises: ValueError if input epoch outside the -2000/4000 range.

        >>> epoch = Epoch(1993, 10, 1.0)
        >>> sta1 = Mercury.station_longitude_1(epoch)
        >>> y, m, d = sta1.get_date()
        >>> print(y)
        1993
        >>> print(m)
        10
        >>> print(round(d, 4))
        25.9358
        """

        # First check that input value is of correct types
        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input type")
        # Check that the input epoch is within valid range
        y = epoch.year()
        if y < -2000.0 or y > 4000.0:
            raise ValueError("Epoch outside the -2000/4000 range")
        # Set some specific constants for Mercury's inferior conjunction
        a = 2451612.023
        b = 115.8774771
        m0 = 63.5867
        m1 = 114.2088742
        k = round((365.2425 * y + 1721060.0 - a) / b)
        jde0 = a + k * b
        m = m0 + k * m1
        m = Angle(m).to_positive()
        m = m.rad()
        t = (jde0 - 2451545.0) / 36525.0
        corr = (-11.0761 + 0.0003 * t
                + sin(m) * (-4.7321 + t * (0.0023 + t * 0.00002))
                + cos(m) * (-1.323 - t * 0.0156)
                + sin(2.0 * m) * (0.227 - t * 0.0046)
                + cos(2.0 * m) * (0.7184 + t * (0.0013 - t * 0.00002))
                + sin(3.0 * m) * (0.0638 + t * 0.0016)
                + cos(3.0 * m) * (-0.1655 + t * 0.0007)
                + sin(4.0 * m) * (-0.0395 - t * 0.0003)
                + cos(4.0 * m) * (0.0247 - t * 0.0006)
                + sin(5.0 * m) * (0.0131)
                + cos(5.0 * m) * (-0.0008 + t * 0.0002))
        to_return = jde0 + corr
        return Epoch(to_return)

    @staticmethod
    def station_longitude_2(epoch):
        """This method computes the time of the 2nd station in longitude
        (i.e. when the planet is stationary and begins to move eastward -
        prograde - among the starts) closest to the given epoch.

        :param epoch: Epoch close to the desired inferior conjunction
        :type epoch: :py:class:`Epoch`

        :returns: Time when the 2nd station in longitude happens, as an Epoch
        :rtype: :py:class:`Epoch`
        :raises: TypeError if input value is of wrong type.
        :raises: ValueError if input epoch outside the -2000/4000 range.

        >>> epoch = Epoch(1993, 10, 1.0)
        >>> sta2 = Mercury.station_longitude_2(epoch)
        >>> y, m, d = sta2.get_date()
        >>> print(y)
        1993
        >>> print(m)
        11
        >>> print(round(d, 4))
        15.0724
        """

        # First check that input value is of correct types
        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input type")
        # Check that the input epoch is within valid range
        y = epoch.year()
        if y < -2000.0 or y > 4000.0:
            raise ValueError("Epoch outside the -2000/4000 range")
        # Set some specific constants for Mercury's inferior conjunction
        a = 2451612.023
        b = 115.8774771
        m0 = 63.5867
        m1 = 114.2088742
        k = round((365.2425 * y + 1721060.0 - a) / b)
        jde0 = a + k * b
        m = m0 + k * m1
        m = Angle(m).to_positive()
        m = m.rad()
        t = (jde0 - 2451545.0) / 36525.0
        corr = (11.1343 - 0.0001 * t
                + sin(m) * (-3.9137 + t * (0.0073 + t * 0.00002))
                + cos(m) * (-3.3861 + t * (-0.0128 + t * 0.00001))
                + sin(2.0 * m) * (0.5222 + t * (-0.004 - t * 0.00002))
                + cos(2.0 * m) * (0.5929 + t * (0.0039 - t * 0.00002))
                + sin(3.0 * m) * (-0.0593 + t * 0.0018)
                + cos(3.0 * m) * (-0.1733 * t * (-0.0007 + t * 0.00001))
                + sin(4.0 * m) * (-0.0053 - t * 0.0006)
                + cos(4.0 * m) * (0.0476 - t * 0.0001)
                + sin(5.0 * m) * (0.007 + t * 0.0002)
                + cos(5.0 * m) * (-0.0115 + t * 0.0001))
        to_return = jde0 + corr
        return Epoch(to_return)

    @staticmethod
    def perihelion_aphelion(epoch, perihelion=True):
        """This method computes the time of Perihelion (or Aphelion) closer to
        a given epoch.

        :param epoch: Epoch close to the desired Perihelion (or Aphelion)
        :type epoch: :py:class:`Epoch`
        :param peihelion: If True, the epoch of the closest Perihelion is
            computed, if False, the epoch of the closest Aphelion is found.
        :type bool:

        :returns: The epoch of the desired Perihelion (or Aphelion)
        :rtype: :py:class:`Epoch`
        :raises: TypeError if input values are of wrong type.

        >>> epoch = Epoch(2000, 1, 1.0)
        >>> e = Mercury.perihelion_aphelion(epoch)
        >>> y, m, d, h, mi, s = e.get_full_date()
        >>> print(y)
        2000
        >>> print(m)
        2
        >>> print(d)
        15
        >>> print(h)
        18
        >>> epoch = Epoch(2000, 3, 1.0)
        >>> e = Mercury.perihelion_aphelion(epoch, perihelion=False)
        >>> y, m, d, h, mi, s = e.get_full_date()
        >>> print(y)
        2000
        >>> print(m)
        3
        >>> print(d)
        30
        >>> print(h)
        17
        """

        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input value")
        # First approximation
        k = 4.15201 * (epoch.year() - 2000.12)
        if perihelion:
            k = round(k)
        else:
            k = round(k + 0.5) - 0.5
        jde = 2451590.257 + k * 87.96934963
        # Compute the epochs half a day before and after
        jde_before = jde - 0.5
        jde_after = jde + 0.5
        # Compute the Sun-Mercury distance for each epoch
        l, b, r_b = Mercury.geometric_heliocentric_position(Epoch(jde_before))
        l, b, r = Mercury.geometric_heliocentric_position(Epoch(jde))
        l, b, r_a = Mercury.geometric_heliocentric_position(Epoch(jde_after))
        # Call an interpolation object
        m = Interpolation([jde_before, jde, jde_after], [r_b, r, r_a])
        sol = m.minmax()
        return Epoch(sol)

    @staticmethod
    def passage_nodes(epoch, ascending=True):
        """This function computes the time of passage by the nodes (ascending
        or descending) of Mercury, nearest to the given epoch.

        :param epoch: Epoch closest to the node passage
        :type epoch: :py:class:`Epoch`
        :param ascending: Whether the time of passage by the ascending (True)
            or descending (False) node will be computed
        :type ascending: bool

        :returns: Tuple containing:
            - Time of passage through the node (:py:class:`Epoch`)
            - Radius vector when passing through the node (in AU, float)
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> epoch = Epoch(2019, 1, 1)
        >>> time, r = Mercury.passage_nodes(epoch)
        >>> year, month, day = time.get_date()
        >>> print(year)
        2018
        >>> print(month)
        11
        >>> print(round(day, 1))
        24.7
        >>> print(round(r, 4))
        0.3143
        """

        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input types")
        # Get the orbital parameters
        l, a, e, i, ome, arg = Mercury.orbital_elements_mean_equinox(epoch)
        # Compute the time of passage through perihelion
        t = Mercury.perihelion_aphelion(epoch)
        # Get the time of passage through the node
        time, r = passage_nodes_elliptic(arg, e, a, t, ascending)
        return time, r

    @staticmethod
    def magnitude(sun_dist, earth_dist, phase_angle):
        """This function computes the approximate magnitude of Mercury.

        :param sun_dist: Distance from Mercury to Sun, in Astronomical Units
        :type sun_dist: float
        :param earth_dist: Distance Mercury to Earth, in Astronomical Units
        :type earth_dist: float
        :param phase_angle: Mercury phase angle
        :type phase_angle: float, :py:class:`Angle`

        :returns: Mercury's magnitude
        :rtype: float
        :raises: TypeError if input values are of wrong type.
        """

        if not (isinstance(sun_dist, float) and isinstance(earth_dist, float)
                and isinstance(phase_angle, (float, Angle))):
            raise TypeError("Invalid input types")
        i = float(phase_angle)
        i50 = i - 50.0
        m = (1.16 + 5.0 * log10(sun_dist * earth_dist) + 0.02838 * i50
             + 0.0001023 * i50 * i50)
        return round(m, 1)
