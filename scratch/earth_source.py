class Earth(object):
    """
    Class Earth models the figure of the Earth surface and, with the help of a
    configurable reference ellipsoid, provides a set of handy method to compute
    different parameters, like the distance between two points on the surface.

    Please note that here we depart a little bit from Meeus' book because the
    Earth class uses the **World Geodetic System 1984 (WGS84)** as the default
    reference ellipsoid, instead of the International Astronomical Union 1974,
    which Meeus uses. This change is done because WGS84 is regarded as more
    modern.
    """

    def __init__(self, ellipsoid=WGS84):
        """Earth constructor.

        It takes a reference ellipsoid as input. If not provided, the ellipsoid
        used is the WGS84 by default.

        :param ellipsoid: Reference ellipsoid to be used. WGS84 by default.
        :type radians: :class:`Ellipsoid`

        :returns: Earth object.
        :rtype: :py:class:`Earth`
        :raises: TypeError if input value is of wrong type.
        """

        # Set an invalid ellipsoid by default
        self._ellip = Ellipsoid(0.0, 0.0, 0.0)
        self.set(ellipsoid)  # Let's use 'set()' method

    def set(self, ellipsoid):
        """Method used to define an Earth object.

        It takes a reference ellipsoid as input. If not provided, the ellipsoid
        used is the WGS84 by default.

        :param ellipsoid: Reference ellipsoid to be used. WGS84 by default.
        :type radians: :class:`Ellipsoid`

        :returns: None
        :rtype: None
        :raises: TypeError if input value is of wrong type.
        """

        if isinstance(ellipsoid, Ellipsoid):
            self._ellip = ellipsoid
        else:
            raise TypeError("Invalid input value")
        return

    def __str__(self):
        """Method used when trying to print the Earth object. It essentially
        returns the corresponting '__str__()' method from the reference
        ellipsoid being used.

        :returns: Semi-major equatorial radius, flattening and angular velocity
           of the current reference ellipsoid, as a string.
        :rtype: string

        >>> e = Earth()
        >>> s = str(e)
        >>> v = s.split(':')
        >>> print(v[0] + '|' + str(round(float(v[1]), 14)) + '|' + v[2] )
        6378137.0|0.00335281066475|7.292115e-05
        """

        return str(self._ellip)

    def __repr__(self):
        """Method providing the 'official' string representation of the object.
        It provides a valid expression that could be used to recreate the
        object.

        :returns: As string with a valid expression to recreate the object
        :rtype: string
        """

        return "{}(ellipsoid=Ellipsoid({}, {}, {}))".format(
            self.__class__.__name__, self._ellip._a, self._ellip._f,
            self._ellip._omega
        )

    def rho(self, latitude):
        """Method to compute the rho term, which is the observer distance to
        the center of the Earth, when the observer is at sea level. In this
        case, the Earth's equatorial radius is taken as unity.

        :param latitude: Geodetical or geographical latitude of the observer,
            in degrees
        :type latitude: int, float, :class:`Angle`

        :returns: Rho: Distance to the center of the Earth from sea level. It
            is a ratio with respect to Earth equatorial radius.
        :rtype: float
        :raises: TypeError if input value is of wrong type.

        >>> e = Earth(ellipsoid=IAU76)
        >>> round(e.rho(0.0), 1)
        1.0
        """

        if not isinstance(latitude, (int, float, Angle)):
            raise TypeError("Invalid input value")
        if isinstance(latitude, (int, float)):
            phi = radians(latitude)  # Convert to radians
        else:
            phi = latitude.rad()  # It is an Angle. Call method rad()
        return (0.9983271 + 0.0016764 * cos(2.0 * phi)
                - 0.0000035 * cos(4.0 * phi))

    def rho_sinphi(self, latitude, height):
        """Method to compute the rho*sin(phi') term, needed in the calculation
        of diurnal parallaxes, eclipses and occulatitions.

        :param latitude: Geodetical or geographical latitude of the observer,
            in degrees
        :type latitude: int, float, :class:`Angle`
        :param height: Height of the observer above the sea level, in meters
        :type height: int, float

        :returns: rho*sin(phi') term
        :rtype: float
        :raises: TypeError if input value is of wrong type.

        >>> lat = Angle(33, 21, 22.0)
        >>> e = Earth(ellipsoid=IAU76)
        >>> round(e.rho_sinphi(lat, 1706), 6)
        0.546861
        """

        if not (
            isinstance(latitude, (int, float, Angle))
            and isinstance(height, (int, float))
        ):
            raise TypeError("Invalid input value")
        if isinstance(latitude, (int, float)):
            phi = radians(latitude)  # Convert to radians
        else:
            phi = latitude.rad()  # It is an Angle. Call method rad()
        b_a = self._ellip.b() / self._ellip._a
        u = atan(b_a * tan(phi))
        return b_a * sin(u) + height / self._ellip._a * sin(phi)

    def rho_cosphi(self, latitude, height):
        """Method to compute the rho*cos(phi') term, needed in the calculation
        of diurnal parallaxes, eclipses and occulatitions.

        :param latitude: Geodetical or geographical latitude of the observer,
            in degrees
        :type latitude: int, float, :class:`Angle`
        :param height: Height of the observer above the sea level, in meters
        :type height: int, float

        :returns: rho*cos(phi') term
        :rtype: float
        :raises: TypeError if input value is of wrong type.

        >>> lat = Angle(33, 21, 22.0)
        >>> e = Earth(ellipsoid=IAU76)
        >>> round(e.rho_cosphi(lat, 1706), 6)
        0.836339
        """

        if not (
            isinstance(latitude, (int, float, Angle))
            and isinstance(height, (int, float))
        ):
            raise TypeError("Invalid input value")
        if isinstance(latitude, (int, float)):
            phi = radians(latitude)  # Convert to radians
        else:
            phi = latitude.rad()  # It is an Angle. Call method rad()
        b_a = self._ellip.b() / self._ellip._a
        u = atan(b_a * tan(phi))
        return cos(u) + height / self._ellip._a * cos(phi)

    def rp(self, latitude):
        """Method to compute the radius of the parallel circle at the given
        latitude.

        :param latitude: Geodetical or geographical latitude of the observer,
            in degrees
        :type latitude: int, float, :class:`Angle`

        :returns: Radius of the parallel circle at given latitude, in meters
        :rtype: float
        :raises: TypeError if input value is of wrong type.

        >>> e = Earth(ellipsoid=IAU76)
        >>> round(e.rp(42.0), 1)
        4747001.2
        """

        if not isinstance(latitude, (int, float, Angle)):
            raise TypeError("Invalid input value")
        if isinstance(latitude, (int, float)):
            phi = radians(latitude)  # Convert to radians
        else:
            phi = latitude.rad()  # It is an Angle. Call method rad()
        a = self._ellip._a
        e = self._ellip.e()
        return (a * cos(phi)) / sqrt(1.0 - e * e * sin(phi) * sin(phi))

    def linear_velocity(self, latitude):
        """Method to compute the linear velocity of a point at latitude, due
        to the rotation of the Earth.

        :param latitude: Geodetical or geographical latitude of the observer,
            in degrees
        :type latitude: int, float, :class:`Angle`

        :returns: Linear velocity of a point at latitude, in meters per second
        :rtype: float
        :raises: TypeError if input value is of wrong type.

        >>> e = Earth(ellipsoid=IAU76)
        >>> round(e.linear_velocity(42.0), 2)
        346.16
        """

        if not isinstance(latitude, (int, float, Angle)):
            raise TypeError("Invalid input value")
        omega = self._ellip._omega
        return omega * self.rp(latitude)

    def rm(self, latitude):
        """Method to compute the radius of curvature of the Earth's meridian
        at the given latitude.

        :param latitude: Geodetical or geographical latitude of the observer,
            in degrees
        :type latitude: int, float, :class:`Angle`

        :returns: Radius of curvature of the Earth's meridian at the given
            latitude, in meters
        :rtype: float
        :raises: TypeError if input value is of wrong type.

        >>> e = Earth(ellipsoid=IAU76)
        >>> round(e.rm(42.0), 1)
        6364033.3
        """

        if not isinstance(latitude, (int, float, Angle)):
            raise TypeError("Invalid input value")
        if isinstance(latitude, (int, float)):
            phi = radians(latitude)  # Convert to radians
        else:
            phi = latitude.rad()  # It is an Angle. Call method rad()
        a = self._ellip._a
        e = self._ellip.e()
        return (a * (1.0 - e * e)) / (1.0 - e * e * sin(phi) * sin(phi)) ** 1.5

    def distance(self, lon1, lat1, lon2, lat2):
        """This method computes the distance between two points on the Earth's
        surface using the method from H. Andoyer.

        .. note:: We will consider that positions 'East' and 'South' are
            negative.

        :param lon1: Longitude of the first point, in degrees
        :type lon1: int, float, :class:`Angle`
        :param lat1: Geodetical or geographical latitude of the first point,
            in degrees
        :type lat1: int, float, :class:`Angle`
        :param lon2: Longitude of the second point, in degrees
        :type lon2: int, float, :class:`Angle`
        :param lat2: Geodetical or geographical latitude of the second point,
            in degrees
        :type lat2: int, float, :class:`Angle`

        :returns: Tuple with distance between the two points along Earth's
            surface, and approximate error, in meters
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> e = Earth(ellipsoid=IAU76)
        >>> lon1 = Angle(-2, 20, 14.0)
        >>> lat1 = Angle(48, 50, 11.0)
        >>> lon2 = Angle(77, 3, 56.0)
        >>> lat2 = Angle(38, 55, 17.0)
        >>> dist, error = e.distance(lon1, lat1, lon2, lat2)
        >>> round(dist, 0)
        6181628.0
        >>> error
        69.0
        >>> lon1 = Angle(-2.09)
        >>> lat1 = Angle(41.3)
        >>> lon2 = Angle(73.99)
        >>> lat2 = Angle(40.75)
        >>> dist, error = e.distance(lon1, lat1, lon2, lat2)
        >>> round(dist, 0)
        6176760.0
        >>> error
        69.0
        """

        if not (
            isinstance(lon1, (int, float, Angle))
            and isinstance(lat1, (int, float, Angle))
            and isinstance(lon2, (int, float, Angle))
            and isinstance(lat2, (int, float, Angle))
        ):
            raise TypeError("Invalid input value")
        if isinstance(lon1, (int, float)):
            l1 = radians(lon1)  # Convert to radians
        else:
            l1 = lon1.rad()  # It is an Angle. Call method rad()
        if isinstance(lat1, (int, float)):
            phi1 = radians(lat1)  # Convert to radians
        else:
            phi1 = lat1.rad()  # It is an Angle. Call method rad()
        if isinstance(lon2, (int, float)):
            l2 = radians(lon2)  # Convert to radians
        else:
            l2 = lon2.rad()  # It is an Angle. Call method rad()
        if isinstance(lat2, (int, float)):
            phi2 = radians(lat2)  # Convert to radians
        else:
            phi2 = lat2.rad()  # It is an Angle. Call method rad()
        f = (phi1 + phi2) / 2.0
        g = (phi1 - phi2) / 2.0
        lam = (l1 - l2) / 2.0
        sin2g = sin(g) ** 2
        cos2g = cos(g) ** 2
        cos2f = cos(f) ** 2
        sin2f = sin(f) ** 2
        sin2lam = sin(lam) ** 2
        cos2lam = cos(lam) ** 2
        s = sin2g * cos2lam + cos2f * sin2lam
        c = cos2g * cos2lam + sin2f * sin2lam
        omega = atan(sqrt(s / c))
        r = sqrt(s * c) / omega
        d = 2.0 * omega * self._ellip._a
        h1 = (3.0 * r - 1.0) / (2.0 * c)
        h2 = (3.0 * r + 1.0) / (2.0 * s)
        fe = self._ellip._f
        dist = d * (1.0 + fe * (h1 * sin2f * cos2g - h2 * cos2f * sin2g))
        error = round(dist * fe * fe, 0)
        return dist, error

    @staticmethod
    def geometric_heliocentric_position(epoch, tofk5=True):
        """This method computes the geometric heliocentric position of the
        Earth for a given epoch, using the VSOP87 theory.

        :param epoch: Epoch to compute Earth position, as an Epoch object
        :type epoch: :py:class:`Epoch`
        :param tofk5: Whether or not the small correction to convert to the FK5
            system will be applied or not
        :type tofk5: bool

        :returns: A tuple with the heliocentric longitude and latitude (as
            :py:class:`Angle` objects), and the radius vector (as a float,
            in astronomical units), in that order
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> epoch = Epoch(1992, 10, 13.0)
        >>> l, b, r = Earth.geometric_heliocentric_position(epoch, tofk5=False)
        >>> print(round(l.to_positive(), 6))
        19.907297
        >>> print(b.dms_str(n_dec=3))
        -0.744''
        >>> print(round(r, 8))
        0.99760852
        """

        # NOTE: In page 169, Meeus gives a different value for the LONGITUDE
        # (19.907372 degrees) as the one presented above (19.906016 degrees).
        # After many checks and tests, I came to the conclusion that the result
        # above is the right one, and Meeus' result is wrong

        return geometric_vsop_pos(epoch, VSOP87_L, VSOP87_B, VSOP87_R, tofk5)

    @staticmethod
    def apparent_heliocentric_position(epoch, nutation=True):
        """This method computes the apparent heliocentric position of the
        Earth for a given epoch, using the VSOP87 theory.

        :param epoch: Epoch to compute Earth position, as an Epoch object
        :type epoch: :py:class:`Epoch`
        :param nutation: Whether the nutation correction will be applied
        :type nutation: bool

        :returns: A tuple with the heliocentric longitude and latitude (as
            :py:class:`Angle` objects), and the radius vector (as a float,
            in astronomical units), in that order
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> epoch = Epoch(1992, 10, 13.0)
        >>> lon, lat, r = Earth.apparent_heliocentric_position(epoch)
        >>> print(round(lon.to_positive(), 6))
        19.905986
        >>> print(lat.dms_str(n_dec=3))
        -0.721''
        >>> print(round(r, 8))
        0.99760852
        """

        return apparent_vsop_pos(epoch, VSOP87_L, VSOP87_B, VSOP87_R, nutation)

    @staticmethod
    def geometric_heliocentric_position_j2000(epoch, tofk5=True):
        """This method computes the geometric heliocentric position of the
        Earth for a given epoch, using the VSOP87 theory, referred to the
        equinox J2000.0.

        :param epoch: Epoch to compute Earth position, as an Epoch object
        :type epoch: :py:class:`Epoch`
        :param tofk5: Whether or not the small correction to convert to the FK5
            system will be applied or not
        :type tofk5: bool

        :returns: A tuple with the heliocentric longitude and latitude (as
            :py:class:`Angle` objects), and the radius vector (as a float,
            in astronomical units), in that order
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.
        """

        return geometric_vsop_pos(
            epoch, VSOP87_L_J2000, VSOP87_B_J2000, VSOP87_R, tofk5
        )

    @staticmethod
    def orbital_elements_mean_equinox(epoch):
        """This method computes the orbital elements of Earth for the mean
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
        >>> l, a, e, i, ome, arg = Earth.orbital_elements_mean_equinox(epoch)
        >>> print(round(l, 6))
        272.716028
        >>> print(round(a, 8))
        1.00000102
        >>> print(round(e, 7))
        0.0166811
        >>> print(round(i, 6))
        0.0
        >>> print(round(ome, 5))
        174.71534
        >>> print(round(arg, 6))
        -70.651889
        """

        return orbital_elements(epoch, ORBITAL_ELEM, ORBITAL_ELEM)

    @staticmethod
    def orbital_elements_j2000(epoch):
        """This method computes the orbital elements of Earth for the
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
        >>> l, a, e, i, ome, arg = Earth.orbital_elements_j2000(epoch)
        >>> print(round(l, 6))
        271.801199
        >>> print(round(a, 8))
        1.00000102
        >>> print(round(e, 7))
        0.0166811
        >>> print(round(i, 6))
        0.008544
        >>> print(round(ome, 5))
        174.71534
        >>> print(round(arg, 6))
        -71.566717
        """

        return orbital_elements(epoch, ORBITAL_ELEM, ORBITAL_ELEM_J2000)

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

        >>> epoch = Epoch(1989, 11, 20.0)
        >>> e = Earth.perihelion_aphelion(epoch)
        >>> y, m, d, h, mi, s = e.get_full_date()
        >>> print(y)
        1990
        >>> print(m)
        1
        >>> print(d)
        4
        >>> print(h)
        17
        >>> epoch = Epoch(2000, 4, 1.0)
        >>> e = Earth.perihelion_aphelion(epoch, perihelion=False)
        >>> y, m, d, h, mi, s = e.get_full_date()
        >>> print(y)
        2000
        >>> print(m)
        7
        >>> print(d)
        3
        >>> print(h)
        23
        >>> print(mi)
        51
        >>> epoch = Epoch(2003, 3, 10.0)
        >>> e = Earth.perihelion_aphelion(epoch)
        >>> y, m, d, h, mi, s = e.get_full_date()
        >>> print(y)
        2003
        >>> print(m)
        1
        >>> print(d)
        4
        >>> print(h)
        5
        >>> print(mi)
        1
        >>> epoch = Epoch(2009, 5, 1.0)
        >>> e = Earth.perihelion_aphelion(epoch, perihelion=False)
        >>> y, m, d, h, mi, s = e.get_full_date()
        >>> print(y)
        2009
        >>> print(m)
        7
        >>> print(d)
        4
        >>> print(h)
        1
        >>> print(mi)
        41
        """

        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input value")
        # First approximation
        k = 0.99997 * (epoch.year() - 2000.01)
        if perihelion:
            k = round(k)
        else:
            k = round(k + 0.5) - 0.5
        jde = 2451547.507 + k * (365.2596358 + k * 0.0000000156)
        # Compute correction to first approximation
        a1 = Angle(328.41 + 132.788585 * k)
        a2 = Angle(316.13 + 584.903153 * k)
        a3 = Angle(346.20 + 450.380738 * k)
        a4 = Angle(136.95 + 659.306737 * k)
        a5 = Angle(249.52 + 329.653368 * k)
        if perihelion:
            corr = (1.278 * sin(a1.rad()) - 0.055 * sin(a2.rad())
                    - 0.091 * sin(a3.rad()) - 0.056 * sin(a4.rad())
                    - 0.045 * sin(a5.rad()))
        else:
            corr = (-1.352 * sin(a1.rad()) + 0.061 * sin(a2.rad())
                    + 0.062 * sin(a3.rad()) + 0.029 * sin(a4.rad())
                    + 0.031 * sin(a5.rad()))
        jde += corr
        # Compute the epochs half a day before and after
        jde_before = jde - 0.5
        jde_after = jde + 0.5
        # Compute the Sun-Earth distance for each epoch
        l, b, r_b = Earth.geometric_heliocentric_position(Epoch(jde_before))
        l, b, r = Earth.geometric_heliocentric_position(Epoch(jde))
        l, b, r_a = Earth.geometric_heliocentric_position(Epoch(jde_after))
        # Call an interpolation object
        m = Interpolation([jde_before, jde, jde_after], [r_b, r, r_a])
        sol = m.minmax()
        return Epoch(sol)

    @staticmethod
    def passage_nodes(epoch, ascending=True):
        """This function computes the time of passage by the nodes (ascending
        or descending) of Earth, nearest to the given epoch.

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
        >>> time, r = Earth.passage_nodes(epoch)
        >>> year, month, day = time.get_date()
        >>> print(year)
        2019
        >>> print(month)
        3
        >>> print(round(day, 1))
        15.0
        >>> print(round(r, 4))
        0.9945
        """

        if not isinstance(epoch, Epoch):
            raise TypeError("Invalid input types")
        # Get the orbital parameters
        l, a, e, i, ome, arg = Earth.orbital_elements_mean_equinox(epoch)
        # Compute the time of passage through perihelion
        t = Earth.perihelion_aphelion(epoch)
        # Get the time of passage through the node
        time, r = passage_nodes_elliptic(arg, e, a, t, ascending)
        return time, r

    @staticmethod
    def parallax_correction(right_ascension, declination, latitude, distance,
                            hour_angle, height=0.0):
        """This function computes the parallaxes in right ascension and
        declination in order to obtain the topocentric values.

        :param right_ascension: Geocentric right ascension, as an
            :py:class:`Angle` object
        :type right_ascension: :py:class:`Angle`
        :param declination: Geocentric declination, as an
            :py:class:`Angle` object
        :type declination: :py:class:`Angle`
        :param latitude: Latitude of the observation point
        :type latitude: :py:class:`Angle`
        :param distance: Distance from the celestial object to the Earth, in
            Astronomical Units
        :type distance: float
        :param hour_angle: Geocentric hour angle of the celestial object, as an
            :py:class:`Angle`
        :type hour_angle: :py:class:`Angle`
        :param heigth: Height of observation point above sea level, in meters
        :type height: float

        :returns: Tuple containing the topocentric right ascension and
            declination
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> right_ascension = Angle(22, 38, 7.25, ra=True)
        >>> declination = Angle(-15, 46, 15.9)
        >>> latitude = Angle(33, 21, 22)
        >>> distance = 0.37276
        >>> hour_angle = Angle(288.7958)
        >>> topo_ra, topo_dec = Earth.parallax_correction(right_ascension, \
                                                          declination, \
                                                          latitude, distance, \
                                                          hour_angle)
        >>> print(topo_ra.ra_str(n_dec=2))
        22h 38' 8.54''
        >>> print(topo_dec.dms_str(n_dec=1))
        -15d 46' 30.0''
        """

        if not (isinstance(right_ascension, Angle)
                and isinstance(declination, Angle)
                and isinstance(latitude, Angle)
                and isinstance(distance, float)
                and isinstance(hour_angle, Angle)
                and isinstance(height, float)):
            raise TypeError("Invalid input types")
        # Let's start computing the equatorial horizontal parallax
        ang = Angle(0, 0, 8.794)
        sin_pi = sin(ang.rad()) / distance
        # Also, the values related to the latitude
        e = Earth()
        rho_sinphi = e.rho_sinphi(latitude, height)
        rho_cosphi = e.rho_cosphi(latitude, height)
        # Now, let's compute the correction for the right ascension
        delta_a = atan2(-rho_cosphi * sin_pi * sin(hour_angle.rad()),
                        cos(declination.rad()) - rho_cosphi * sin_pi
                        * cos(hour_angle.rad()))
        delta_a = Angle(delta_a, radians=True)
        # And finally, the declination already corrected
        dec = atan2((sin(declination.rad()) - rho_sinphi * sin_pi)
                    * cos(delta_a.rad()),
                    cos(declination.rad()) - rho_cosphi * sin_pi
                    * cos(hour_angle.rad()))
        dec = Angle(dec, radians=True)
        return (right_ascension + delta_a), dec

    @staticmethod
    def parallax_ecliptical(longitude, latitude, semidiameter, obs_lat,
                            obliquity, sidereal_time, distance, height=0.0):
        """This function computes the topocentric coordinates of a celestial
        body (Moon or planet) directly from its geocentric values in ecliptical
        coordinates.

        :param longitude: Geocentric ecliptical longitude as an
            :py:class:`Angle`
        :type longitude: :py:class:`Angle`
        :param latitude: Geocentric ecliptical latitude as an :py:class:`Angle`
        :type latitude: :py:class:`Angle`
        :param semidiameter: Geocentric semidiameter as an :py:class:`Angle`
        :type semidiameter: :py:class:`Angle`
        :param obs_lat: Latitude of the observation point
        :type obs_lat: :py:class:`Angle`
        :param obliquity: Obliquity of the eliptic, as an :py:class:`Angle`
        :type obliquity: :py:class:`Angle`
        :param sidereal_time: Local sidereal time, as an :py:class:`Angle`
        :type sidereal_time: :py:class:`Angle`
        :param distance: Distance from the celestial object to the Earth, in
            Astronomical Units
        :type distance: float
        :param heigth: Height of observation point above sea level, in meters
        :type height: float

        :returns: Tuple containing the topocentric longitude, latitude and
            semidiameter
        :rtype: tuple
        :raises: TypeError if input values are of wrong type.

        >>> longitude = Angle(181, 46, 22.5)
        >>> latitude = Angle(2, 17, 26.2)
        >>> semidiameter = Angle(0, 16, 15.5)
        >>> obs_lat = Angle(50, 5, 7.8)
        >>> obliquity = Angle(23, 28, 0.8)
        >>> sidereal_time = Angle(209, 46, 7.9)
        >>> distance = 0.0024650163
        >>> topo_lon, topo_lat, topo_diam = \
                Earth.parallax_ecliptical(longitude, latitude, semidiameter, \
                                          obs_lat, obliquity, sidereal_time, \
                                          distance)
        >>> print(topo_lon.dms_str(n_dec=1))
        181d 48' 5.0''
        >>> print(topo_lat.dms_str(n_dec=1))
        1d 29' 7.1''
        >>> print(topo_diam.dms_str(n_dec=1))
        16' 25.5''
        """

        if not (isinstance(longitude, Angle)
                and isinstance(latitude, Angle)
                and isinstance(semidiameter, Angle)
                and isinstance(obs_lat, Angle)
                and isinstance(obliquity, Angle)
                and isinstance(sidereal_time, Angle)
                and isinstance(distance, float)
                and isinstance(height, float)):
            raise TypeError("Invalid input types")
        # Let's start computing the equatorial horizontal parallax
        ang = Angle(0, 0, 8.794)
        sin_pi = sin(ang.rad()) / distance
        # Also, the values related to the latitude
        e = Earth()
        rho_sinphi = e.rho_sinphi(obs_lat, height)
        rho_cosphi = e.rho_cosphi(obs_lat, height)
        # Let's compute some auxiliary quantities
        lonr = longitude.rad()
        latr = latitude.rad()
        semir = semidiameter.rad()
        sidr = sidereal_time.rad()
        oblr = obliquity.rad()
        n = cos(lonr) * cos(latr) - rho_cosphi * sin_pi * cos(sidr)
        # Now, compute the topocentric longitude
        topo_lon = atan2(sin(lonr) * cos(latr)
                         - sin_pi * (rho_sinphi * sin(oblr)
                                     + rho_cosphi * cos(oblr) * sin(sidr)), n)
        topo_lon = Angle(topo_lon, radians=True).to_positive()
        tlonr = topo_lon.rad()
        # Compute the topocentric latitude
        topo_lat = atan2(cos(tlonr) * (sin(latr)
                                       - sin_pi * (rho_sinphi * cos(oblr)
                                                   - rho_cosphi * sin(oblr)
                                                   * sin(sidr))), n)
        topo_lat = Angle(topo_lat, radians=True).to_positive()
        # Watch out: Latitude is only valid in the +/-90 deg range
        if abs(topo_lat) > 90.0:
            topo_lat = topo_lat - 180.0
        tlatr = topo_lat.rad()
        # And finally, let's compute the topocentric semidiameter
        topo_semi = asin((cos(tlonr) * cos(tlatr) * sin(semir)) / n)
        topo_semi = Angle(topo_semi, radians=True)
        return topo_lon, topo_lat, topo_semi
